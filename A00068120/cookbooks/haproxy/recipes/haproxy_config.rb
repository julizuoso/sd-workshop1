template '/etc/haproxy/haproxy.cfg' do
	source 'haproxy.erb'
	mode 0644
	owner 'root'
	group 'wheel'
	variables(
		:web_servers => node[:web_servers]
	)
end

bash 'open port' do
	user 'root'
	code <<-EOH
	iptables -I INPUT 5 -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT
	service iptables save
	EOH
end

service 'haproxy' do
  action [ :start, :enable ]
end 
