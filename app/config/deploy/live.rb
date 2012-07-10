server 'imagepush.to', :app, :web, :primary => true
set :deploy_to, "/mnt/www/imagepush"

# Made vendor not shared. It is slower, but more secure.
set :vendors_mode, "install"

set :branch, "master"

set :shared_children,     [app_path + "/logs", "web/sitemap.xml.gz"]