# Minggu 6

Dokumentasi Minggu 6 tersedia di:

- `rancangan-bangunan-minggu-06.md`: rencana implementasi dan demo.
- `docs-api.md`: ringkasan endpoint dan status code.
- [`../api.md`](../api.md): endpoint API dan contoh request.

Script otorisasi API:

```bash
STUDENT_TOKEN=<token> \
LECTURER_A_TOKEN=<token> \
STUDENT_COURSE_ID=<id> \
LECTURER_A_COURSE_ID=<id> \
LECTURER_B_COURSE_ID=<id> \
LECTURER_A_ID=<id> \
LECTURER_A_ASSIGNMENT_ID=<id> \
LECTURER_B_ASSIGNMENT_ID=<id> \
./scripts/test-api.sh
```

Tambahkan `ADMIN_TOKEN=<token>` juga. Script memerlukan Bash, curl, dan jq. ID assignment harus benar-benar berada pada course masing-masing.

Jalur frontend minggu 7–16: **Blade + Livewire**. Kontrak API v1 proyek: [`../api.md`](../api.md).
