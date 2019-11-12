mkdir -p build/
tar -czf build/dist.tar.gz Bootstrapping Component Controller Models Services Views Bootstrap.php plugin.json plugin.png README.md
mkdir -p build/dist/Frontend/RpayRatePay
tar -xzf build/dist.tar.gz -C build/dist/Frontend/RpayRatePay
rm -rf build/dist.tar.gz
cd build/dist
zip -r RpayRatePay.zip Frontend


