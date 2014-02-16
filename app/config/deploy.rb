set :stage_dir, 'app/config/deploy'
set :stages, %w(live urgent)
require 'capistrano/ext/multistage'

set :application, "imagepush.to"
set :deploy_to, "/mnt/www/imagepush"

set :repository,  "git@github.com:antonbabenko/imagepush2.git"
set :scm,         :git
set :scm_verbose, false

set :deploy_via,  :remote_cache    # dev/test/update servers
#set :deploy_via,  :rsync_with_remote_cache # prod

set :model_manager, "doctrine"

set :use_composer, true
set :composer_bin, "/usr/local/bin/composer"

set :composer_options,  "--verbose --prefer-source --no-interaction --no-scripts"

set :use_sudo,      false
set :user, "ec2-user"
ssh_options[:paranoid] = false
ssh_options[:forward_agent] = true
default_run_options[:pty] = true
ssh_options[:keys] = ENV["EC2_ANTON_CERT_FILE"]

set :dump_assetic_assets, true
set :normalize_asset_timestamps, false
set :interactive_mode, false
set :symfony_env_prod, "prod"

set :shared_files,        ["app/config/parameters.yml", "web/sitemap.xml.gz"]
set :shared_children,     [app_path + "/logs"]

# Be more verbose by uncommenting the following line
logger.level = Logger::MAX_LEVEL

#################################################################
# Extra commands to run before or after #########################
#################################################################

# Copy vendors from previous release
before 'symfony:composer:install', 'composer:copy_vendors'
before 'symfony:composer:update', 'composer:copy_vendors'

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
