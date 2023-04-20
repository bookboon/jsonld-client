
test: psalm phpunit
	@printf "\n\n\033[0;32mAll tests passed, you are ready to push commits\033[0m\n"


phpunit:
	@vendor/bin/phpunit \
		--testdox \
		-c .
psalm:
	@vendor/bin/psalm
