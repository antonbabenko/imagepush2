server 'anton-server', :app, :web, :primary => true

set :branch, "master"

# The following line tells Capifony to deploy the last Git tag.
# Since Jenkins creates and pushes a tag following a successful build this should always be the last tested version of the code.
# set :branch, `git describe --tags \`git rev-list --tags --max-count=1\``