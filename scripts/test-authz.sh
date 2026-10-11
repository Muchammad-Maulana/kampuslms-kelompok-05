#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://127.0.0.1:8000}"
API="$BASE_URL/api/v1"
TEMP_COURSE_ID=""
TEMP_ASSIGNMENT_ID=""
ADMIN_TOKEN=""
LECTURER_TOKEN=""

PHP_BIN="${PHP_BIN:-php}"

for command_name in curl "$PHP_BIN"; do
	command -v "$command_name" >/dev/null || {
		echo "$command_name diperlukan untuk menjalankan tes otorisasi."
		exit 1
	}
done

login() {
	local email="$1"
	local response
	response="$(curl -fsS "$API/auth/login" \
		-H 'Accept: application/json' \
		-H 'Content-Type: application/json' \
		-d "$("$PHP_BIN" -r 'echo json_encode(["email" => $argv[1], "password" => "password", "device_name" => "week-7-authz"]);' "$email")")"
	json_value "$response" token
}

json_value() {
	"$PHP_BIN" -r '
		$value = json_decode($argv[1], true, flags: JSON_THROW_ON_ERROR);
		foreach (explode(".", $argv[2]) as $key) {
				$key = ctype_digit($key) ? (int) $key : $key;
				if (! is_array($value) || ! array_key_exists($key, $value)) {
						exit(1);
				}
				$value = $value[$key];
		}
		if (is_string($value) || is_numeric($value)) {
				echo $value;
		} else {
				echo json_encode($value, JSON_THROW_ON_ERROR);
		}
	' "$1" "$2"
}

cleanup() {
	if [[ -n "$TEMP_ASSIGNMENT_ID" && -n "$TEMP_COURSE_ID" && -n "$LECTURER_TOKEN" ]]; then
		curl -sS -X DELETE "$API/courses/$TEMP_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" \
			-H "Authorization: Bearer $LECTURER_TOKEN" >/dev/null || true
	fi
	if [[ -n "$TEMP_COURSE_ID" && -n "$ADMIN_TOKEN" ]]; then
		curl -sS -X DELETE "$API/courses/$TEMP_COURSE_ID" \
			-H "Authorization: Bearer $ADMIN_TOKEN" >/dev/null || true
	fi
}
trap cleanup EXIT

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

STUDENT_TOKEN="$(login mahasiswa@kampuslms.test)"
LECTURER_TOKEN="$(login dosen@kampuslms.test)"
ADMIN_TOKEN="$(login admin@kampuslms.test)"
LECTURER_ID="$(json_value "$(curl -fsS "$API/me" -H "Authorization: Bearer $LECTURER_TOKEN")" data.id)"
STUDENT_COURSES="$(curl -fsS "$API/courses" -H "Authorization: Bearer $STUDENT_TOKEN")"
ADMIN_COURSES="$(curl -fsS "$API/courses" -H "Authorization: Bearer $ADMIN_TOKEN")"
STUDENT_COURSE_ID="$(json_value "$STUDENT_COURSES" data.0.id)"
OTHER_COURSE_ID="$("$PHP_BIN" -r '
	$response = json_decode($argv[1], true, flags: JSON_THROW_ON_ERROR);
	foreach ($response["data"] as $course) {
			if (($course["lecturer"]["id"] ?? null) !== (int) $argv[2]) {
					echo $course["id"];
					exit(0);
			}
	}
	exit(1);
' "$ADMIN_COURSES" "$LECTURER_ID")"

# Authentication and object-level course access.
expect_status courses_without_auth 401 "$API/courses"
expect_status student_course_list 200 -H "Authorization: Bearer $STUDENT_TOKEN" "$API/courses"
expect_status student_enrolled_course 200 -H "Authorization: Bearer $STUDENT_TOKEN" "$API/courses/$STUDENT_COURSE_ID"
expect_status lecturer_other_course 403 -H "Authorization: Bearer $LECTURER_TOKEN" "$API/courses/$OTHER_COURSE_ID"

# Create an owned course for this run so the mutation checks do not depend on random seed ownership.
COURSE_CODE="W7AUTHZ$(date +%s)"
COURSE_BODY="$("$PHP_BIN" -r 'echo json_encode(["code" => $argv[1], "name" => "Temporary Week 7 authz course", "sks" => 3, "lecturer_id" => (int) $argv[2], "status" => "active"]);' "$COURSE_CODE" "$LECTURER_ID")"
expect_status course_create_student 403 -X POST "$API/courses" -H "Authorization: Bearer $STUDENT_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$COURSE_BODY"
capture_status course_create_admin 201 -X POST "$API/courses" -H "Authorization: Bearer $ADMIN_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$COURSE_BODY"
TEMP_COURSE_ID="$(json_value "$RESPONSE_BODY" data.id)"

expect_status student_cannot_list_other_course_assignments 403 -H "Authorization: Bearer $STUDENT_TOKEN" "$API/courses/$TEMP_COURSE_ID/assignments"
expect_status student_cannot_create_assignment 403 -X POST "$API/courses/$TEMP_COURSE_ID/assignments" -H "Authorization: Bearer $STUDENT_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"title":"forbidden","instructions":"forbidden","due_at":"2030-01-01T00:00:00Z"}'
capture_status lecturer_create_assignment 201 -X POST "$API/courses/$TEMP_COURSE_ID/assignments" -H "Authorization: Bearer $LECTURER_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"title":"Temporary Week 7 assignment","instructions":"Temporary authz test.","due_at":"2030-01-01T00:00:00Z","status":"draft"}'
TEMP_ASSIGNMENT_ID="$(json_value "$RESPONSE_BODY" data.id)"

# A request cannot change the assignment owner; only the course lecturer can mutate it.
expect_status student_cannot_update_assignment 403 -X PATCH "$API/courses/$TEMP_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" -H "Authorization: Bearer $STUDENT_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"title":"forbidden"}'
capture_status lecturer_update_assignment 200 -X PATCH "$API/courses/$TEMP_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" -H "Authorization: Bearer $LECTURER_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d '{"title":"Temporary Week 7 assignment updated"}'
capture_status lecturer_delete_assignment 204 -X DELETE "$API/courses/$TEMP_COURSE_ID/assignments/$TEMP_ASSIGNMENT_ID" -H "Authorization: Bearer $LECTURER_TOKEN"
TEMP_ASSIGNMENT_ID=""

# Verify admin-only course update and delete operations.
UPDATED_COURSE_BODY="$("$PHP_BIN" -r 'echo json_encode(["code" => $argv[1], "name" => "Temporary Week 7 authz course updated", "sks" => 3, "lecturer_id" => (int) $argv[2], "status" => "active"]);' "$COURSE_CODE" "$LECTURER_ID")"
expect_status student_cannot_update_course 403 -X PATCH "$API/courses/$TEMP_COURSE_ID" -H "Authorization: Bearer $STUDENT_TOKEN" -H 'Accept: application/json' -H 'Content-Type: application/json' -d "$UPDATED_COURSE_BODY"
capture_status admin_delete_course 204 -X DELETE "$API/courses/$TEMP_COURSE_ID" -H "Authorization: Bearer $ADMIN_TOKEN"
TEMP_COURSE_ID=""