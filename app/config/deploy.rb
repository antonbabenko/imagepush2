set :stage_dir, 'app/config/deploy'
set :stages, %w(live urgent)
require 'capistrano/ext/multistage'

set :application, "imagepush.to"
set :deploy_to, "/mnt/www/imagepush"

set :repository,  "git@github.com:antonbabenko/imagepush2.git"
set :scm,         :git
set :scm_verbose, false

set :deploy_via,  :remote_cache

set :model_manager, "doctrine"

set :use_composer, true
set :composer_bin, "/usr/local/bin/composer"
set :copy_vendors, true

set :composer_options, "--no-dev --prefer-dist --optimize-autoloader --no-progress"

set :use_sudo,      false
set :user, "ec2-user"
ssh_options[:paranoid] = false
ssh_options[:forward_agent] = true
default_run_options[:pty] = true
ssh_options[:keys] = ENV["EC2_ANTON_CERT_FILE"]

set :assets_install, true
set :dump_assetic_assets, true
set :normalize_asset_timestamps, false
set :interactive_mode, false
set :symfony_env_prod, "prod"
set :keep_releases, 3

set :shared_files,        ["app/config/parameters.yml", "web/sitemap.xml.gz"]
set :shared_children,     [app_path + "/logs"]

# Be more verbose by uncommenting the following line
logger.level = Logger::MAX_LEVEL

#################################################################
# Extra commands to run before or after #########################
#################################################################

# Remove old releases
after "deploy", "deploy:cleanup"

# Clear APC cache
after "deploy", "symfony:apc_clear"

#################################################################
#################################################################
#################################################################

namespace :symfony do
  task :apc_clear do
    capifony_pretty_print "--> Clear APC cache"

    run "#{try_sudo} sh -c 'cd #{latest_release} && #{php_bin} #{symfony_console} apc:clear --env=#{symfony_env_prod}'"

    capifony_puts_ok
  end
end

#################################################################
