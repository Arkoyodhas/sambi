# Regression: installed clone first-login -> forced password change -> dashboard/admin menus without DB error
# Usage: CLONE_PORTS=8201,8202 CLONE_OLDPASS=rahasia123 pytest /app/backend/tests/test_clone_install_flow.py
import os
import re
import pytest
import requests

PORTS = [p for p in os.environ.get("CLONE_PORTS", "").split(",") if p]
OLD = os.environ.get("CLONE_OLDPASS", "rahasia123")
NEW = "TEST_Baru12345"
MENUS = ["berita", "pengumuman", "agenda", "galeri", "penduduk", "keluarga", "permohonan", "pengaduan",
         "pengaturan", "menu", "users", "roles", "audit", "perangkat", "layanan", "dokumen", "umkm", "sotk",
         "hiburan", "chat", "kunjungan", "feedback", "faq", "event", "musik", "sistem", "akun", "dashboard"]
DBERR = "kesalahan database"


def login(s, base, user, pw):
    r = s.get(f"{base}/admin/login")
    m = re.search(r"Captcha: <strong>(\d+) \+ (\d+)", r.text)
    assert m, "captcha not found"
    csrf = re.search(r'name="csrf_token" value="([^"]*)"', r.text).group(1)
    return s.post(f"{base}/admin/login", data={"csrf_token": csrf, "login": user, "password": pw,
                                                "captcha": int(m.group(1)) + int(m.group(2))}, allow_redirects=False)


@pytest.mark.parametrize("port", PORTS)
def test_full_flow(port):
    base = f"http://localhost:{port}"
    s = requests.Session()
    r = login(s, base, "admin", OLD)
    assert r.status_code in (302, 303), r.text[:300]
    r = s.get(f"{base}/admin/dashboard", allow_redirects=False)
    assert r.status_code in (302, 303) and "admin/akun" in r.headers.get("Location", ""), "must force akun"
    r = s.get(f"{base}/admin/akun")
    assert r.status_code == 200 and DBERR not in r.text
    csrf = re.search(r'name="csrf_token" value="([^"]*)"', r.text).group(1)
    r = s.post(f"{base}/admin/akun/simpan", data={"csrf_token": csrf, "name": "Admin", "phone": "081234567890",
                                                  "password_lama": OLD, "password_baru": NEW,
                                                  "password_konfirmasi": NEW}, allow_redirects=False)
    assert r.status_code in (302, 303) and "admin/dashboard" in r.headers.get("Location", ""), (r.status_code, r.headers, r.text[:500])
    r = s.get(f"{base}/admin/dashboard")
    assert r.status_code == 200 and DBERR not in r.text, r.text[:1500]
    s.get(f"{base}/admin/logout")
    s2 = requests.Session()
    r = login(s2, base, "admin", NEW)
    assert r.status_code in (302, 303) and "login" not in r.headers.get("Location", ""), r.headers
    bad = []
    for m in MENUS:
        r = s2.get(f"{base}/admin/{m}")
        if r.status_code != 200 or DBERR in r.text or "/admin/login" in r.url or "/admin/akun" in r.url and m != "akun":
            bad.append((m, r.status_code, re.findall(r"SQLSTATE[^<]*", r.text)[:1]))
    assert not bad, bad
    for p in ["", "berita", "pengumuman", "agenda", "galeri", "layanan", "profil"]:
        r = requests.get(f"{base}/{p}")
        assert DBERR not in r.text, p
