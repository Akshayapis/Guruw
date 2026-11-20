#!/usr/bin/env python3
"""Check local checkout, dashboard and navigation files needed at runtime."""
from pathlib import Path
root = Path(__file__).resolve().parents[1]
paths = [
    'assets/css/cashpe.css', 'assets/js/cashpe.js',
    'assets/dist/css/adminx.css', 'assets/dist/js/sweetalert.min.js',
    'assets/dist/js/adminx.js', 'assets/dist/js/vendor.js',
    'assets/dist/js/custom-new.js', 'assets/dist/js/qrcode.min.js',
    'assets/dist/js/paytmTxnStatus.js', 'assets/dist/js/upitxnStatus.js',
    'assets/vendor/fontawesome/css/all.min.css',
    'assets/vendor/fontawesome/webfonts/fa-solid-900.woff2',
    'images/cashpe-logo.svg', 'images/cashpe-logo-inverse.svg',
    'js/jquery.min.js', 'js/bootstrap.min.js', 'js/bootstrap.bundle.min.js',
]
paths += ['assets/img/' + name + '.svg' for name in (
    'gpay', 'paytm', 'phonepe', 'bhim_logo', 'amazonpay', 'bank_paytm',
    'bank_sbi', 'smartgateway', 'bhimupi', 'upiapps', 'loading', 'success',
    'failed', 'rectangle')]
missing = [name for name in paths if not (root / name).is_file()]
if missing:
    raise SystemExit('Missing assets: ' + ', '.join(missing))
print('PASS: {} bundled UI assets'.format(len(paths)))
