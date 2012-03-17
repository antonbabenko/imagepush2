# Notes (2.1.2012):
# 1) For production deployment deploy_via "rsync_with_remote_cache", but for test/update deployments use "remote_cache"
# 2) Production should not share "vendor" directory, because site is down when they are updating
# 3) Make sure that it doesn't use --reinstall line 145 in /Library/Ruby/Gems/1.8/gems/capifony-2.1.3/lib/symfony2.rb

set :stage_dir, 'app/config/deploy'
require 'capistrano/ext/multistage'
set :stages, %w(live urgent)

set :application, "imagepush.to"

set :repository,  "git@github.com:antonbabenko/imagepush2.git"
set :scm,         :git
#set :deploy_via,  :copy
set :deploy_via,  :remote_cache    # dev/test/update servers
#set :deploy_via,  :rsync_with_remote_cache # prod
set :scm_verbose, false

set :keep_releases,  3
set :use_sudo,      false

set :user, "ec2-user"
ssh_options[:forward_agent] = true
default_run_options[:pty] = true
ssh_options[:keys] = ENV["EC2_NE_CERT_FILE"]

set :shared_files,        ["app/config/parameters.ini", "bin/backup_settings"]
set :shared_children,     [app_path + "/logs", "web/uploads", "vendor"]

set :update_vendors, true
set :dump_assetic_assets, true

set :normalize_asset_timestamps, false
