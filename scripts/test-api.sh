#!/usr/bin/env bash
set -euo pipefail

# Jalankan, misalnya:
#   BASE_URL=http://127.0.0.1:8126 \
#   STUDENT_TOKEN=... LECTURER_A_TOKEN=... LECTURER_B_TOKEN=... ADMIN_TOKEN=... \
#   LECTURER_A_ID=... STUDENT_FORBIDDEN_COURSE_ID=... \
#   LECTURER_B_COURSE_ID=... ./scripts/test-api.sh

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
API="$BASE_URL/api/v1"

STUDENT_TOKEN="${STUDENT_TOKEN:-}"
LECTURER_A_TOKEN="${LECTURER_A_TOKEN:-}"
LECTURER_B_TOKEN="${LECTURER_B_TOKEN:-}"
ADMIN_TOKEN="${ADMIN_TOKEN:-}"
LECTURER_A_ID="${LECTURER_A_ID:-}"
STUDENT_FORBIDDEN_COURSE_ID="${STUDENT_FORBIDDEN_COURSE_ID:-}"
LECTURER_B_COURSE_ID="${LECTURER_B_COURSE_ID:-}"

command -v curl >/dev/null || { echo 'Gagal: curl diperlukan.' >&2; exit 1; }
command -v jq >/dev/null || { echo 'Gagal: jq diperlukan untuk membaca ID response.' >&2; exit 1; }

for name in STUDENT_TOKEN LECTURER_A_TOKEN LECTURER_B_TOKEN ADMIN_TOKEN \
  LECTURER_A_ID STUDENT_FORBIDDEN_COURSE_ID LECTURER_B_COURSE_ID; do
  if [[ -z "${!name}" ]]; then
    echo "Gagal: set $name sebelum menjalankan pengujian otorisasi." >&2
    exit 1
  fi
done

PASS_COUNT=0
FAIL_COUNT=0
LAST_BODY=''

expect_status() {
  local condition="$1"
  local expected="$2"
  shift 2

  local response actual
  response="$(curl -sS -w $'\n%{http_code}' "$@")"
  actual="${response##*$'\n'}"
  LAST_BODY="${response%$'\n'*}"

  if [[ "$actual" == "$expected" ]]; then
    printf 'PASS %-58s HTTP %s\n' "$condition" "$actual"
    PASS_COUNT=$((PASS_COUNT + 1))
  else
    printf 'FAIL %-58s HTTP %s (expected %s)\n' "$condition" "$actual" "$expected" >&2
    printf '      response: %s\n' "$LAST_BODY" >&2
    FAIL_COUNT=$((FAIL_COUNT + 1))
    return 1
  fi
}

json_headers=(-H 'Accept: application/json' -H 'Content-Type: application/json')
COURSE_BODY='{"code":"CURL-AUTH-TEMP","name":"Course curl authorization","sks":3,"lecturer_id":'"$LECTURER_A_ID"',"status":"active"}'
ASSIGNMENT_BODY='{"title":"Assignment curl authorization","instructions":"Data sementara untuk tes otorisasi.","due_at":"2030-01-01T00:00:00Z","status":"published"}'

echo "Menguji $API"
echo

# GET /me: tanpa token 401; token mahasiswa yang benar 200.
# Kondisi dosen A ke data dosen B tidak berlaku karena /me selalu mengembalikan
# identitas pemilik token, bukan resource milik user lain.
expect_status 'GET /me tanpa token -> 401' 401 "$API/me"
expect_status 'GET /me token mahasiswa -> 200' 200 -H "Authorization: Bearer $STUDENT_TOKEN" "$API/me"

# GET /courses: tanpa token 401; mahasiswa yang terautentikasi 200.
# Endpoint index memfilter course berdasarkan user, sehingga kondisi lintas pemilik
# diuji pada GET /courses/{id}.
expect_status 'GET /courses tanpa token -> 401' 401 "$API/courses"
expect_status 'GET /courses token mahasiswa -> 200' 200 -H "Authorization: Bearer $STUDENT_TOKEN" "$API/courses"

# GET /courses/{id}: mahasiswa yang tidak terdaftar 403; dosen A membaca course B 403;
# dosen B sebagai pemilik membaca course B 200.
expect_status 'GET /courses/{id} tanpa token -> 401' 401 "$API/courses/$LECTURER_B_COURSE_ID"
expect_status 'GET /courses/{id} mahasiswa -> 403' 403 \
  -H "Authorization: Bearer $STUDENT_TOKEN" "$API/courses/$STUDENT_FORBIDDEN_COURSE_ID"
expect_status 'GET /courses/{id} dosen A ke course B -> 403' 403 \
  -H "Authorization: Bearer $LECTURER_A_TOKEN" "$API/courses/$LECTURER_B_COURSE_ID"
expect_status 'GET /courses/{id} dosen B pemilik -> 200' 200 \
  -H "Authorization: Bearer $LECTURER_B_TOKEN" "$API/courses/$LECTURER_B_COURSE_ID"

# POST /courses: mahasiswa dan dosen bukan admin ditolak 403; admin membuat 201.
expect_status 'POST /courses tanpa token -> 401' 401 -X POST "$API/courses" "${json_headers[@]}" -d "$COURSE_BODY"
expect_status 'POST /courses token mahasiswa -> 403' 403 -X POST "$API/courses" \
  -H "Authorization: Bearer $STUDENT_TOKEN" "${json_headers[@]}" -d "$COURSE_BODY"
expect_status 'POST /courses token dosen A -> 403' 403 -X POST "$API/courses" \
  -H "Authorization: Bearer $LECTURER_A_TOKEN" "${json_headers[@]}" -d "$COURSE_BODY"
expect_status 'POST /courses token admin -> 201' 201 -X POST "$API/courses" \
  -H "Authorization: Bearer $ADMIN_TOKEN" "${json_headers[@]}" -d "$COURSE_BODY"
TEMP_COURSE_ID="$(printf '%s' "$LAST_BODY" | jq -r '.data.id')"

# PUT /courses/{id}: mahasiswa dan dosen ditolak 403; admin yang berhak 200.
UPDATED_COURSE_BODY='{"code":"CURL-AUTH-TEMP","name":"Course curl authorization updated","sks":3,"lecturer_id":'"$LECTURER_A_ID"',"status":"active"}'
expect_status 'PUT /courses/{id} tanpa token -> 401' 401 -X PUT "$API/courses/$TEMP_COURSE_ID" "${json_headers[@]}" -d "$UPDATED_COURSE_BODY"
expect_status 'PUT /courses/{id} token mahasiswa -> 403' 403 -X PUT "$API/courses/$TEMP_COURSE_ID" \
  -H "Authorization: Bearer $STUDENT_TOKEN" "${json_headers[@]}" -d "$UPDATED_COURSE_BODY"
expect_status 'PUT /courses/{id} token dosen A -> 403' 403 -X PUT "$API/courses/$TEMP_COURSE_ID" \
  -H "Authorization: Bearer $LECTURER_A_TOKEN" "${json_headers[@]}" -d "$UPDATED_COURSE_BODY"
expect_status 'PUT /courses/{id} token admin -> 200' 200 -X PUT "$API/courses/$TEMP_COURSE_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN" "${json_headers[@]}" -d "$UPDATED_COURSE_BODY"

# DELETE /courses/{id}: mahasiswa dan dosen ditolak 403; admin yang berhak 204.
expect_status 'DELETE /courses/{id} tanpa token -> 401' 401 -X DELETE "$API/courses/$TEMP_COURSE_ID"
expect_status 'DELETE /courses/{id} token mahasiswa -> 403' 403 -X DELETE "$API/courses/$TEMP_COURSE_ID" \
  -H "Authorization: Bearer $STUDENT_TOKEN"
expect_status 'DELETE /courses/{id} token dosen A -> 403' 403 -X DELETE "$API/courses/$TEMP_COURSE_ID" \
  -H "Authorization: Bearer $LECTURER_A_TOKEN"
expect_status 'DELETE /courses/{id} token admin -> 204' 204 -X DELETE "$API/courses/$TEMP_COURSE_ID" \
  -H "Authorization: Bearer $ADMIN_TOKEN"

# GET /courses/{course}/assignments: mahasiswa yang tidak terdaftar 403;
# dosen A ke course B 403; dosen B pemilik course B 200.
expect_status 'GET assignments tanpa token -> 401' 401 "$API/courses/$LECTURER_B_COURSE_ID/assignments"
expect_status 'GET assignments mahasiswa -> 403' 403 \
  -H "Authorization: Bearer $STUDENT_TOKEN" "$API/courses/$STUDENT_FORBIDDEN_COURSE_ID/assignments"
expect_status 'GET assignments dosen A ke course B -> 403' 403 \
  -H "Authorization: Bearer $LECTURER_A_TOKEN" "$API/courses/$LECTURER_B_COURSE_ID/assignments"
expect_status 'GET assignments dosen B pemilik -> 200' 200 \
  -H "Authorization: Bearer $LECTURER_B_TOKEN" "$API/courses/$LECTURER_B_COURSE_ID/assignments"

# POST assignments: mahasiswa dan dosen A ke course B 403; dosen B pemilik 201.
expect_status 'POST assignments tanpa token -> 401' 401 -X POST "$API/courses/$LECTURER_B_COURSE_ID/assignments" "${json_headers[@]}" -d "$ASSIGNMENT_BODY"
expect_status 'POST assignments token mahasiswa -> 403' 403 -X POST "$API/courses/$LECTURER_B_COURSE_ID/assignments" \
  -H "Authorization: Bearer $STUDENT_TOKEN" "${json_headers[@]}" -d "$ASSIGNMENT_BODY"
expect_status 'POST assignments dosen A ke course B -> 403' 403 -X POST "$API/courses/$LECTURER_B_COURSE_ID/assignments" \
  -H "Authorization: Bearer $LECTURER_A_TOKEN" "${json_headers[@]}" -d "$ASSIGNMENT_BODY"
expect_status 'POST assignments dosen B pemilik -> 201' 201 -X POST "$API/courses/$LECTURER_B_COURSE_ID/assignments" \
  -H "Authorization: Bearer $LECTURER_B_TOKEN" "${json_headers[@]}" -d "$ASSIGNMENT_BODY"
TEMP_ASSIGNMENT_ID="$(printf '%s' "$LAST_BODY" | jq -r '.data.id')"

# GET assignment detail: mahasiswa yang tidak terdaftar dan dosen A ke data B 403;
# dosen B sebagai pemilik 200.
expect_status 'GET assignment/{id} tanpa token -> 401' 401 "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID"
expect_status 'GET assignment/{id} mahasiswa -> 403' 403 \
  -H "Authorization: Bearer $STUDENT_TOKEN" "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID"
expect_status 'GET assignment/{id} dosen A ke data B -> 403' 403 \
  -H "Authorization: Bearer $LECTURER_A_TOKEN" "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID"
expect_status 'GET assignment/{id} dosen B pemilik -> 200' 200 \
  -H "Authorization: Bearer $LECTURER_B_TOKEN" "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID"

# PUT assignment: mahasiswa dan dosen A ke data B 403; dosen B pemilik 200.
UPDATE_ASSIGNMENT_BODY='{"title":"Assignment curl authorization updated"}'
expect_status 'PUT assignment/{id} tanpa token -> 401' 401 -X PUT "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" "${json_headers[@]}" -d "$UPDATE_ASSIGNMENT_BODY"
expect_status 'PUT assignment/{id} token mahasiswa -> 403' 403 -X PUT "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" \
  -H "Authorization: Bearer $STUDENT_TOKEN" "${json_headers[@]}" -d "$UPDATE_ASSIGNMENT_BODY"
expect_status 'PUT assignment/{id} dosen A ke data B -> 403' 403 -X PUT "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" \
  -H "Authorization: Bearer $LECTURER_A_TOKEN" "${json_headers[@]}" -d "$UPDATE_ASSIGNMENT_BODY"
expect_status 'PUT assignment/{id} dosen B pemilik -> 200' 200 -X PUT "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" \
  -H "Authorization: Bearer $LECTURER_B_TOKEN" "${json_headers[@]}" -d "$UPDATE_ASSIGNMENT_BODY"

# DELETE assignment: mahasiswa dan dosen A ke data B 403; dosen B pemilik 204.
expect_status 'DELETE assignment/{id} tanpa token -> 401' 401 -X DELETE "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID"
expect_status 'DELETE assignment/{id} token mahasiswa -> 403' 403 -X DELETE "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" \
  -H "Authorization: Bearer $STUDENT_TOKEN"
expect_status 'DELETE assignment/{id} dosen A ke data B -> 403' 403 -X DELETE "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" \
  -H "Authorization: Bearer $LECTURER_A_TOKEN"
expect_status 'DELETE assignment/{id} dosen B pemilik -> 204' 204 -X DELETE "$API/courses/$LECTURER_B_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" \
  -H "Authorization: Bearer $LECTURER_B_TOKEN"

echo
printf 'Selesai: %d lulus, %d gagal.\n' "$PASS_COUNT" "$FAIL_COUNT"
(( FAIL_COUNT == 0 ))
