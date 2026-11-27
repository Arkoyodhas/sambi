# Error page behaviour on installed clone (debug=false): admin sees detail, anonymous sees generic + Kode only
# Usage: CLONE_PORT=8202 CLONE_PASS=TEST_Baru12345 CLONE_ENGINE=m8 CLONE_DB=t1_m8 pytest test_clone_error_page.py
import os
import re
import subprocess
import pytest
import requests
from test_clone_install_flow import login

PORT = os.environ.get("CLONE_PORT")
PASS = os.environ.get("CLONE_PASS")
DB = os.environ.get("CLONE_DB")
M = ["mysql", "-uroot"] + (["-S", "/root/m8.sock"] if os.environ.get("CLONE_ENGINE") == "m8" else [])
BASE = f"http://localhost:{PORT}"


def sql(q):
    subprocess.run(M + [DB, "-e", q], check=True)


@pytest.fixture(scope="module", autouse=True)
def broken_table():
    sql("RENAME TABLE complaints TO complaints_t1bak")
    yield
    sql("RENAME TABLE complaints_t1bak TO complaints")


def test_admin_sees_detail():
    s = requests.Session()
    r = login(s, BASE, "admin", PASS)
    assert r.status_code in (302, 303)
    r = s.get(f"{BASE}/admin/dashboard")
    print(r.status_code, re.sub(r"<[^>]+>", " ", r.text)[:800])
    assert r.status_code == 500
    assert "kesalahan database" in r.text
    assert "Detail teknis (hanya terlihat oleh admin)" in r.text
    assert "SQLSTATE" in r.text and "complaints" in r.text
    assert re.search(r"Kode: [0-9A-F]{8}", r.text)


def test_anonymous_generic_only():
    for path in ["pengaduan", "pengaduan/lacak", "layanan/pengaduan"]:
        r = requests.get(f"{BASE}/{path}", headers={"User-Agent": "Mozilla/5.0 Chrome"})
        if "kesalahan database" in r.text:
            print(path, r.status_code, re.sub(r"<[^>]+>", " ", r.text)[:400])
            assert "SQLSTATE" not in r.text and "Detail teknis" not in r.text
            assert re.search(r"Kode: [0-9A-F]{8}", r.text)
            return
    pytest.fail("no public page hit the missing table")
