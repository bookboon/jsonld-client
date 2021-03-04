
test: phpstan psalm phpunit
	@printf "\n\n\033[0;32mAll tests passed, you are ready to push commits\033[0m"


phpunit:
	@vendor/bin/phpunit \
		--testdox \
		-c .
psalm:
	@vendor/bin/psalm

phpstan:
	@vendor/bin/phpstan analyse -c phpstan.neon --no-progress --memory-limit 512M
