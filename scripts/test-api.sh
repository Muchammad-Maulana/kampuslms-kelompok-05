#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
STUDENT_TOKEN="${STUDENT_TOKEN:-}"
LECTURER_A_TOKEN="${LECTURER_A_TOKEN:-}"
ADMIN_TOKEN="${ADMIN_TOKEN:-}"
STUDENT_COURSE_ID="${STUDENT_COURSE_ID:-}"
LECTURER_A_COURSE_ID="${LECTURER_A_COURSE_ID:-}"
LECTURER_B_COURSE_ID="${LECTURER_B_COURSE_ID:-}"
LECTURER_A_ID="${LECTURER_A_ID:-}"
LECTURER_A_ASSIGNMENT_ID="${LECTURER_A_ASSIGNMENT_ID:-}"
LECTURER_B_ASSIGNMENT_ID="${LECTURER_B_ASSIGNMENT_ID:-}"

for name in STUDENT_TOKEN LECTURER_A_TOKEN ADMIN_TOKEN STUDENT_COURSE_ID LECTURER_A_COURSE_ID LECTURER_B_COURSE_ID LECTURER_A_ID LECTURER_A_ASSIGNMENT_ID LECTURER_B_ASSIGNMENT_ID; do
  if [[ -z "${!name}" ]]; then
    echo "Set $name sebelum menjalankan pengujian otorisasi."
    exit 1
  fi
done

command -v curl >/dev/null || { echo 'curl diperlukan.'; exit 1; }
command -v jq >/dev/null || { echo 'jq diperlukan untuk membaca ID response.'; exit 1; }

capture_status() {
  local label="$1"
  local expected="$2"
  shift 2
  local response actual
  response="$(curl -sS -w $'\n%{http_code}' "$@")"
  actual="${response##*$'\n'}"
  RESPONSE_BODY="${response%$'\n'*}"
  printf '%s=%s (expected %s)\n' "$label" "$actual" "$expected"
  [[ "$actual" == "$expected" ]]
}

expect_status() {
  capture_status "$@" >/dev/null
}

API="$BASE_URL/api/v1"
COURSE_BODY='{"code":"APIW6TEMP","name":"Course API sementara","sks":3,"lecturer_id":'"$LECTURER_A_ID"',"status":"active"}'
ASSIGNMENT_BODY='{"title":"Assignment API sementara","instructions":"Data sementara untuk tes otorisasi.","due_at":"2030-01-01T00:00:00Z","status":"published"}'

# GET /courses: tanpa token 401; token sah dengan role mahasiswa 200.
expect_status courses_tanpa_token 401 "$API/courses"
expect_status courses_mahasiswa 200 -H "Authorization: Bearer $STUDENT_TOKEN" "$API/courses"

# GET /courses/{course}: mahasiswa terdaftar 200; dosen A ke course dosen B 403.
expect_status course_tanpa_token 401 "$API/courses/$STUDENT_COURSE_ID"
expect_status course_mahasiswa_terdaftar 200 -H "Authorization: Bearer $STUDENT_TOKEN" "$API/courses/$STUDENT_COURSE_ID"
expect_status course_dosen_lain 403 -H "Authorization: Bearer $LECTURER_A_TOKEN" "$API/courses/$LECTURER_B_COURSE_ID"

# POST /courses: admin 201; mahasiswa dan dosen 403.
expect_status course_create_tanpa_token 401 -X POST "$API/courses" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$COURSE_BODY"
expect_status course_create_mahasiswa 403 -X POST "$API/courses" -H "Authorization: Bearer $STUDENT_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$COURSE_BODY"
expect_status course_create_dosen 403 -X POST "$API/courses" -H "Authorization: Bearer $LECTURER_A_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$COURSE_BODY"
capture_status course_create_admin 201 -X POST "$API/courses" -H "Authorization: Bearer $ADMIN_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$COURSE_BODY"
TEMP_COURSE_ID="$(printf '%s' "$RESPONSE_BODY" | jq -r '.data.id')"

# PUT /courses/{course}: admin 200; mahasiswa/dosen 403.
UPDATED_COURSE_BODY='{"code":"APIW6TEMP","name":"Course API sementara diperbarui","sks":3,"lecturer_id":'"$LECTURER_A_ID"',"status":"active"}'
expect_status course_update_tanpa_token 401 -X PUT "$API/courses/$TEMP_COURSE_ID" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$UPDATED_COURSE_BODY"
expect_status course_update_mahasiswa 403 -X PUT "$API/courses/$TEMP_COURSE_ID" -H "Authorization: Bearer $STUDENT_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$UPDATED_COURSE_BODY"
expect_status course_update_dosen 403 -X PUT "$API/courses/$TEMP_COURSE_ID" -H "Authorization: Bearer $LECTURER_A_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$UPDATED_COURSE_BODY"
expect_status course_update_admin 200 -X PUT "$API/courses/$TEMP_COURSE_ID" -H "Authorization: Bearer $ADMIN_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$UPDATED_COURSE_BODY"

# DELETE /courses/{course}: token non-admin 403; admin 204.
expect_status course_delete_tanpa_token 401 -X DELETE "$API/courses/$TEMP_COURSE_ID"
expect_status course_delete_mahasiswa 403 -X DELETE "$API/courses/$TEMP_COURSE_ID" -H "Authorization: Bearer $STUDENT_TOKEN"
expect_status course_delete_dosen 403 -X DELETE "$API/courses/$TEMP_COURSE_ID" -H "Authorization: Bearer $LECTURER_A_TOKEN"
expect_status course_delete_admin 204 -X DELETE "$API/courses/$TEMP_COURSE_ID" -H "Authorization: Bearer $ADMIN_TOKEN"

# Assignment list: hak baca mahasiswa terdaftar dan dosen pemilik 200, dosen lain 403.
expect_status assignments_tanpa_token 401 "$API/courses/$STUDENT_COURSE_ID/assignments"
expect_status assignments_mahasiswa 200 -H "Authorization: Bearer $STUDENT_TOKEN" "$API/courses/$STUDENT_COURSE_ID/assignments"
expect_status assignments_dosen_a 200 -H "Authorization: Bearer $LECTURER_A_TOKEN" "$API/courses/$LECTURER_A_COURSE_ID/assignments"
expect_status assignments_dosen_b 403 -H "Authorization: Bearer $LECTURER_A_TOKEN" "$API/courses/$LECTURER_B_COURSE_ID/assignments"

# POST assignment: hanya dosen pemilik/admin dapat membuat, response 201.
expect_status assignment_create_tanpa_token 401 -X POST "$API/courses/$LECTURER_A_COURSE_ID/assignments" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$ASSIGNMENT_BODY"
expect_status assignment_create_mahasiswa 403 -X POST "$API/courses/$LECTURER_A_COURSE_ID/assignments" -H "Authorization: Bearer $STUDENT_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$ASSIGNMENT_BODY"
expect_status assignment_create_dosen_lain 403 -X POST "$API/courses/$LECTURER_B_COURSE_ID/assignments" -H "Authorization: Bearer $LECTURER_A_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$ASSIGNMENT_BODY"
capture_status assignment_create_dosen_a 201 -X POST "$API/courses/$LECTURER_A_COURSE_ID/assignments" -H "Authorization: Bearer $LECTURER_A_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$ASSIGNMENT_BODY"
TEMP_ASSIGNMENT_ID="$(printf '%s' "$RESPONSE_BODY" | jq -r '.data.id')"

# Detail assignment dan update: pemilik 200; mahasiswa/dosen lain 403.
expect_status assignment_show_tanpa_token 401 "$API/courses/$LECTURER_A_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID"
expect_status assignment_show_dosen_a 200 -H "Authorization: Bearer $LECTURER_A_TOKEN" "$API/courses/$LECTURER_A_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID"
expect_status assignment_show_dosen_lain 403 -H "Authorization: Bearer $LECTURER_A_TOKEN" "$API/courses/$LECTURER_B_COURSE_ID/assignments/$LECTURER_B_ASSIGNMENT_ID"
expect_status assignment_update_mahasiswa 403 -X PUT "$API/courses/$LECTURER_A_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" -H "Authorization: Bearer $STUDENT_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"title":"Tidak diizinkan"}'
expect_status assignment_update_dosen_lain 403 -X PUT "$API/courses/$LECTURER_B_COURSE_ID/assignments/$LECTURER_B_ASSIGNMENT_ID" -H "Authorization: Bearer $LECTURER_A_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"title":"Tidak diizinkan"}'
expect_status assignment_update_dosen_a 200 -X PUT "$API/courses/$LECTURER_A_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" -H "Authorization: Bearer $LECTURER_A_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"title":"Assignment API diperbarui"}'

# DELETE assignment: pemilik 204; tanpa token/mahasiswa/dosen lain ditolak.
expect_status assignment_delete_tanpa_token 401 -X DELETE "$API/courses/$LECTURER_A_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID"
expect_status assignment_delete_mahasiswa 403 -X DELETE "$API/courses/$LECTURER_A_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" -H "Authorization: Bearer $STUDENT_TOKEN"
expect_status assignment_delete_dosen_lain 403 -X DELETE "$API/courses/$LECTURER_B_COURSE_ID/assignments/$LECTURER_B_ASSIGNMENT_ID" -H "Authorization: Bearer $LECTURER_A_TOKEN"
expect_status assignment_delete_dosen_a 204 -X DELETE "$API/courses/$LECTURER_A_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" -H "Authorization: Bearer $LECTURER_A_TOKEN"
