load 'deploy' if respond_to?(:namespace) # cap2 differentiator
Dir['vendor/bundles/*/*/recipes/*.rb'].each { |bundle| load(bundle) }
load Gem.find_files('symfony2.rb').last.to_s

after "deploy:finalize_update" do
  # run "sudo chmod -R 777 #{latest_release}/#{cache_path}"
  # run "sudo chown -R apache:apache #{latest_release}/#{cache_path}"
  # run "sudo chown -R apache:ec2-user #{latest_release}/#{log_path}"

  # Usually we don't change DB schema so often, so we don't need such prompt every time
  # Another option is to run "cap live symfony:doctrine:migrations:migrate" from command line, but it doens't work for me yet
  # Upcomment this line to prompt migration
  # deploy.migrate

  # Populate properties index
  # run "cd #{latest_release} && #{php_bin} #{symfony_console} foq:elastica:populate"

  # run "sudo chmod -R 777 #{latest_release}/#{cache_path}"

  # Increase timeout in Goutte curl client - (fixed: https://github.com/antonbabenko/imagepush2/issues/16)
  # run "cd #{latest_release} && sed -ie 's/->set(CURLOPT_TIMEOUT, 30);/->set(CURLOPT_TIMEOUT, 300);/' vendor/fabpot/Goutte/Goutte/Client.php"
end

load 'app/config/deploy'