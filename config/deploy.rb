# config valid only for current version of Capistrano
lock '3.11.0'

set :application, 'stage9.oldfag.top'
set :repo_url, 'git@github.com:Chifth/johncms.git'

set :composer_install_flags, '--no-dev --optimize-autoloader'
# Default branch is :master
# ask :branch, `git rev-parse --abbrev-ref HEAD`.chomp
set :branch, "stage9"

# Symfony environment
set :symfony_env,  "prod"

# Symfony application path
set :app_path,              "app"

set :bin_path,              "bin"

set :var_path,              "var"

# Symfony web path
set :web_path,              "public"

# Symfony log path
set :log_path,              fetch(:var_path) + "/log"

# Symfony cache path
set :cache_path,            fetch(:var_path) + "/cache"

# Symfony config file path
set :app_config_path,       fetch(:app_path) + "/config"

# Controllers to clear
set :controllers_to_clear, ["app_*.php"]

# Files that need to remain the same between deploys
set :linked_files,          ['./.env']

# Dirs that need to remain the same between deploys (shared dirs)
set :linked_dirs,           [fetch(:log_path), fetch(:web_path) + "/files"]

# Dirs that need to be writable by the HTTP Server (i.e. cache, log dirs)
set :file_permissions_paths,         [fetch(:log_path), fetch(:cache_path)]

# Name used by the Web Server (i.e. www-data for Apache)
set :file_permissions_users, ['www-data']

# Name used by the Web Server (i.e. www-data for Apache)
set :webserver_user,        "www-data"

# Method used to set permissions (:chmod, :acl, or :chgrp)
set :permission_method,     false

# Execute set permissions
set :use_set_permissions,   false

# Symfony console path
set :symfony_console_path, fetch(:bin_path) + "/console"

# Symfony console flags
set :symfony_console_flags, "--no-debug"

# Assets install path
set :assets_install_path,   fetch(:web_path)

# Assets install flags
set :assets_install_flags,  '--symlink'

# Assetic dump flags
set :assetic_dump_flags,  ''

fetch(:default_env).merge!(symfony_env: fetch(:symfony_env))

SSHKit.config.command_map[:composer] = "php #{shared_path.join("composer.phar")}"

namespace :deploy do
    
  task :postgres_dump do
    on roles(:db) do
      execute "cd /var/www/stage9.oldfag.top/bin && ./dump.sh"
    end
  end
    
  task :schema do
    invoke 'symfony:console', 'doctrine:schema:update', '--no-interaction', 'db'
    invoke 'symfony:console', 'doctrine:schema:validate', '--no-interaction', 'db'
  end
  
  task :fixtures do
    invoke 'symfony:console', 'doctrine:fixtures:load', '--no-interaction', 'db'
  end
  
  task :migrate do
    invoke 'symfony:console', 'doctrine:migrations:migrate', '--no-interaction', 'db'
  end
  
  task :restart_fpm do
    on roles(:web) do
      execute 'sudo /bin/systemctl reload php7.4-fpm.service'
    end
  end
  
  task :supervisor_restart do
    on roles(:web) do
      execute 'sudo supervisorctl restart all'
    end
  end

  task :doctrine_cache_clear do
    invoke 'symfony:console', 'doctrine:cache:clear-metadata', '-e=prod'
    invoke 'symfony:console', 'doctrine:cache:clear-query', '-e=prod'
    invoke 'symfony:console', 'doctrine:cache:clear-result', '-e=prod'
  end
  
  task :dump_rotation do
    on roles(:db) do
      execute "cd /var/www/stage9.oldfag.top/bin && ./rotation.sh"
    end
  end

  before 'deploy:publishing',  'postgres_dump'
  after :starting, 'composer:install_executable'
  
  after  'deploy:updated',     'symfony:assets:install'
  #after  'deploy:updated',     'symfony:assetic:dump'
  before 'deploy:publishing',  'migrate'
  #after  'deploy:migrate',     'doctrine_cache_clear'
  after  'deploy:publishing',  'restart_fpm'
  after  'deploy:publishing',  'supervisor_restart'
  after  'deploy:publishing',  'dump_rotation'

  after :restart, :clear_cache do
    on roles(:web), in: :groups, limit: 3, wait: 10 do
      # Here we can do anything such as:
      # within release_path do
      #   execute :rake, 'cache:clear'
      # end
    end
  end

end