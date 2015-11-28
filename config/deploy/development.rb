role :web, %w{127.0.0.1}

server '127.0.0.1', user: 'vagrant', roles: %w{web}, port: 2222

set :whenever_environment, 'development'
