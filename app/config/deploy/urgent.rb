server 'imagepush.to', :app, :web, :primary => true
set :deploy_to, "/mnt/www/imagepush"

# Made vendor shared to be able to release urgent fixes without fetching all deps once again. It is faster, but less secure.
set :vendors_mode, "install"

set :branch, "master"

set :shared_children,     [app_path + "/logs", "web/files", "vendor"]
