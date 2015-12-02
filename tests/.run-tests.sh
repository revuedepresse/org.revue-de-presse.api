#!/bin/bash

# Run all test suites successively

(for test in `cat package.json | grep 'test-' | grep -v 'test"' | cut -f '1' -d ':' | cut -d '"' -f 2`;\
do /bin/bash -c 'npm run '$test || exit 1; \
done)

exit $?
