
task :clear_symfony_cache do
  invoke 'symfony:console', 'cache:clear', '--env=prod'
end

task :clear_apc_cache do
  invoke 'symfony:console', 'apc:clear', '--env=prod'
end

task :apply_migrations do
  invoke 'symfony:console', 'doctrine:migrations:migrate', '--env=prod'
end

task :install_node_modules do
  on roles(:all) do
    execute "cd #{release_path} && npm install -q"
  end
end

task :install_bower_components do
  invoke 'symfony:console', 'sp:bower:install', '--env=prod', '--interactive=false'
end

task :install_javascript_routing do
  invoke 'symfony:console', 'fos:js-routing:dump', '--env=prod'
  on roles(:all) do
    info capture('sudo /etc/init.d/varnish restart')
  end
end

desc "create empty parameters"
task :parameters_empty do
    on roles(:all) do
        if  not test("[ -f #{shared_path}/app/config/parameters.yml ]")
            upload! StringIO.new('---'), "#{shared_path}/app/config/parameters.yml"
        end
    end
end

# See also https://gist.github.com/carlcraig/377ed30bfa2015ea5049

desc "configure parameters with dist values if not defined"
task :parameters do
    on roles(:all) do
        distParameters = YAML::load(capture("cat #{release_path}/app/config/parameters.yml.dist"))
        if test("[ -f #{shared_path}/app/config/parameters.yml ]")
            parameters = YAML::load(capture("cat #{shared_path}/app/config/parameters.yml"))
            if parameters
                distParameters['parameters'].each do |k,v|
                    if parameters['parameters'][k].nil? && parameters['parameters'].fetch(k, false) == false
                        set :distParameter, ask(k, v)
                        parameters['parameters'][k] = fetch(:distParameter)
                    end
                end
            else
                parameters = YAML::load(capture("cat #{release_path}/app/config/parameters.yml.dist"))
                distParameters['parameters'].each do |k,v|
                    set :distParameter, ask(k, v)
                    parameters['parameters'][k] = fetch(:distParameter)
                end
            end
        else
            parameters = YAML::load(capture("cat #{release_path}/app/config/parameters.yml.dist"))
            distParameters['parameters'].each do |k,v|
                set :distParameter, ask(k, v)
                parameters['parameters'][k] = fetch(:distParameter)
            end
        end
        upload! StringIO.new(parameters.to_yaml), "#{shared_path}/app/config/parameters.yml"
    end
end

desc "configure all parameters"
task :parameters_configure do
    on roles(:all) do
        distParameters = YAML::load(capture("cat #{release_path}/app/config/parameters.yml.dist"))
        if test("[ -f #{shared_path}/app/config/parameters.yml ]")
            parameters = YAML::load(capture("cat #{shared_path}/app/config/parameters.yml"))
            if parameters
                distParameters['parameters'].each do |k,v|
                    if parameters['parameters'][k].nil? && parameters['parameters'].fetch(k, false) == false
                        set :distParameter, ask(k, v)
                        parameters['parameters'][k] = fetch(:distParameter)
                    else
                        set :distParameter, ask(k, parameters['parameters'][k])
                        parameters['parameters'][k] = fetch(:distParameter)
                    end
                end
            else
                parameters = YAML::load(capture("cat #{release_path}/app/config/parameters.yml.dist"))
                distParameters['parameters'].each do |k,v|
                    set :distParameter, ask(k, v)
                    parameters['parameters'][k] = fetch(:distParameter)
                end
            end
        else
            parameters = YAML::load(capture("cat #{release_path}/app/config/parameters.yml.dist"))
            distParameters['parameters'].each do |k,v|
                set :distParameter, ask(k, v)
                parameters['parameters'][k] = fetch(:distParameter)
            end
        end
        upload! StringIO.new(parameters.to_yaml), "#{shared_path}/app/config/parameters.yml"
    end
end
