mkdir -p build/
tar --exclude-from=.release_exclude  -czf build/dist.tar.gz .
mkdir -p build/dist/RpayRatePay
tar -xzf build/dist.tar.gz -C build/dist/RpayRatePay
rm -rf build/dist.tar.gz
cd build/dist
zip -r RpayRatePay.zip RpayRatePay
