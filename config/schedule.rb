every '*/1 * * * *' do
  command "source ## FILL CONFIGURATION ROOT ##/export_dashboard_environment_variables && ## FILL DOCUMENT ROOT ##/current/app/console wtw:job:run -e prod 2>> /var/log/job.run.error.log >> /var/log/job.run.out.log"
end
