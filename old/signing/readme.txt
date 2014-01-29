# Generate the RSA keys in shell to 1024 bits. Each user or even workflow has its own keys:

openssl genrsa -out private.pem 1024
openssl rsa -in private.pem -out public.pem -outform PEM -pubout

# To sign then test the signature of an arbitrary file, in this case it's an image otherwise it would be the workflow zip:

php test.php