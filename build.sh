mkdir -p build/
tar --exclude-from=.release_exclude  -czf build/dist.tar.gz .
mkdir -p build/dist/Frontend/RpayRatePay
tar -xzf build/dist.tar.gz -C build/dist/Frontend/RpayRatePay
rm -rf build/dist.tar.gz
cd build/dist
zip -r RpayRatePay.zip Frontend
