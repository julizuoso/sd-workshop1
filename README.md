# Sistemas distribuidos

## Taller 1
#### Automatización de infraestructura (Vagrant+Chef)
Juliana Zúñiga

#### Descripción:
Se realizó el aprovisionamiento de	un	ambiente	compuesto	por	los	siguientes	elementos:	un servidor	encargado de realizar balanceo de	carga, dos servidores web y un servidor de base de datos.
***

1. Aprovisionamiento de servicios

Las acciones que se deben automatizar para desplegar el balanceador de carga son:

```bash
$ yum install haproxy -y
$ vi /etc/haproxy/proxy.cfg (agregar nodos backend)
$ iptables -I INPUT 5 -p tcp -m state --state NEW -m tcp --dport 80 -j

```

Las acciones que se deben automatizar para desplegar el servicio web son:

```bash
$ yum install httpd php php-mysql mysql -y
$ systemctl enable httpdiptables -I INPUT 5 -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT
$ iptables -I INPUT 5 -p tcp -m state --state NEW -m tcp --dport 80 -j ACCEPT
$ service iptables save
$ vi /var/www/html/index.html (crear una aplicación web simple)
$ vi /var/www/html/select.php
```

Las acciones que se deben automatizar para levantar el servidor de base de datos son:

```bash
$ yum install mysql-server -y
$ systemctl enable mysqld
$ iptables -I INPUT 5 -p tcp -m state --state NEW -m tcp --dport 3306 -j ACCEPT
$ service iptables save
$ /usr/bin/mysql_secure_installation (proveer los datos necesarios)
$ vi create_schema.sql
$ cat create_schema.sql | mysql -u root -pdistribuidos
```
___

2. En el Vagrantfile se aprovisionaron las máquinas: load balancer, web server 1, web server 2 y database server.

```ruby
# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"


Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.ssh.insert_key = false
  config.vbguest.auto_update=false # Deshabilitar actualizaciones automáticas de vbguest
  config.vm.define :lb_server do |lb| # Definir el servidor de balanceo de carga
    lb.vm.box = "centos6" # Importar el box de CentOS6
    lb.vm.network :private_network, ip: "192.168.56.100" # Configurar la IP privada de la VM
    lb.vm.provider :virtualbox do |vb| # Asignar el proveedor de virtualización
      vb.customize ["modifyvm", :id, "--memory", "1024","--cpus", "1", "--name", "lb_server" ] # Configurar los recursos de la VM
    end
    lb.vm.provision :chef_solo do |chef| # Definir Chef solo como herramienta de aprovicionamiento de la VM
      chef.install = false # Deshabilitar la instalación de Chef solo
      chef.cookbooks_path = "cookbooks" # Establecer la ruta donde se encuentran las recetas de aprovisionamiento
      chef.add_recipe "haproxy" # Agregar la receta de aprovisionamiento de haproxy
      chef.json = {
        "web_servers" => [
          {"ip":"192.168.56.101"},
          {"ip":"192.168.56.102"}
         ]
      } # Definir las variables necesarias para configurar el haproxy
    end
  end
  config.vm.define :centos_web_1 do |wb| # Definir el servidor web 1
    wb.vm.box = "centos6" # Importar el box de CentOS6
    wb.vm.network :private_network, ip: "192.168.56.101" # Configurar la IP privada de la VM
    wb.vm.network "public_network", bridge: "eth1", ip:"192.168.131.101", netmask: "255.255.255.0" # Configurar la IP pública de la VM
    wb.vm.provider :virtualbox do |vb| # Asignar el proveedor de virtualización
      vb.customize ["modifyvm", :id, "--memory", "1024","--cpus", "1", "--name", "centos_web_1" ] # Configurar los recursos de la VM
    end
    wb.vm.provision :chef_solo do |chef| # Definir Chef solo como herramienta de aprovicionamiento de la VM
      chef.cookbooks_path = "cookbooks" # Establecer la ruta donde se encuentran las recetas de aprovisionamiento
      chef.add_recipe "apache" # Agregar la receta de aprovisionamiento de apache
      chef.json = {"service_name" => "Web server 1"} # Definir la variable que será usada para mostrar el nombre del servidor en index.html
    end
  end
  config.vm.define :centos_web_2 do |wb| # Definir el servidor web 2
    wb.vm.box = "centos6" # Importar el box de CentOS6
    wb.vm.network :private_network, ip: "192.168.56.102" # Configurar la IP privada de la VM
    wb.vm.network "public_network", bridge: "eth1", ip:"192.168.131.102", netmask: "255.255.255.0" # Configurar la IP pública de la VM
    wb.vm.provider :virtualbox do |vb| # Asignar el proveedor de virtualización
      vb.customize ["modifyvm", :id, "--memory", "1024","--cpus", "1", "--name", "centos_web_2" ] # Configurar los recursos de la VM
    end
    wb.vm.provision :chef_solo do |chef| # Definir Chef solo como herramienta de aprovicionamiento de la VM
      chef.cookbooks_path = "cookbooks" # Establecer la ruta donde se encuentran las recetas de aprovisionamiento
      chef.add_recipe "apache"  # Agregar la receta de aprovisionamiento de haproxy
      chef.json = {"service_name" => "Web server 2"} # Definir la variable que será usada para mostrar el nombre del servidor en index.html
    end
  end
  config.vm.define :centos_database do |db| # Definir el servidor de base de datos
    db.vm.box = "centos6" # Importar el box de CentOS6
    db.vm.network :private_network, ip: "192.168.56.103" # Configurar la IP privada de la VM
    db.vm.network "public_network", bridge: "eth1", ip:"192.168.131.103", netmask: "255.255.255.0" # Configurar la IP pública de la VM
    db.vm.provider :virtualbox do |vb| # Asignar el proveedor de virtualización
      vb.customize ["modifyvm", :id, "--memory", "1024","--cpus", "1", "--name", "centos_database" ] # Configurar los recursos de la VM
    end
    db.vm.provision :chef_solo do |chef| # Definir Chef solo como herramienta de aprovicionamiento de la VM
      chef.cookbooks_path = "cookbooks" # Establecer la ruta donde se encuentran las recetas de aprovisionamiento
      chef.add_recipe "mysql"  # Agregar la receta de aprovisionamiento de mysql
    end
  end
end


```

___

3. Funcionalidades implementadas en los cookbooks

Directorio o archivo | Descripción
--- | ---
cookbooks/ | Directorio que contiene todos los cookbooks usados en la actividad
haproxy/ | Directorio que contiene todo el aprovisionamiento del balanceador de carga
haproxy/recipes/ | Directorio que contiene las recetas de instalación y configuración de haproxy
haproxy/recipes/default.rb | Archivo que especifica las recetas que deben ser usadas para aprovisionar el balanceador de carga
haproxy/recipes/haproxy_config.rb | Archivo donde se carga el archivo de configuración del haproxy añadiendo los nodos de backend (servidores web) que van a ser consultados. Además se abre el puerto 80 en la VM.
haproxy/recipes/haproxy_install.rb | Archivo que indica que el paquete haproxy que debe ser instalado
haproxy/templates/default/haproxy.erb | Archivo de configuración de haproxy que será transferido a la VM
apache/ | Directorio que contiene todo el aprovisionamiento de los servidores web
apache/recipes/ | Directorio que contiene las recetas de instalación y configuración de httpd y mysql
apache/recipes/default.rb | Archivo que especifica las recetas que deben ser usadas para aprovisionar el servicio web
apache/recipes/installapache.rb | Archivo que indica que los paquetes httpd, mysql, php-mysql y php deben ser instalados. Habilita y da inicio al servicio httpd y, además, abre el puerto 80 en la VM.
apache/files/default/ | Directorio que contiene la aplicación web escrita en PHP, la cual consulta a la base de datos
apache/templates/default/index.erb | Archivo que será transferido a la VM y será la página principal del servidor web.
mysql/ | Directorio que contiene todo el aprovisionamiento del servidor de base de datos
mysql/recipes/ | Directorio que contiene las recetas de instalación y configuración de mysql
mysql/recipes/default.rb | Archivo que especifica las recetas que deben ser usadas para aprovisionar la base de datos
mysql/recipes/installmysql.rb | Archivo que indica que los paquetes mysql y mysql-server server deben ser instalados. Además, abre el puerto 3306 en la VM
mysql/files/default/ | Directorio que contiene los archivos de configuración inicial de mysql
mysql/files/default/configure_mysql.sh | Archivo usado para asignar una contraseña de acceso a la base de datos
mysql/files/default/create_schema.sql | Archivo usado para crear una nueva base de datos, una tabla en ella, insertar valores y asignar permisos de acceso para los servidores web

***

4. A continuación se muestra una consulta HTTP a la IP del haproxy.

![alt text](https://image.ibb.co/kSoqoa/w1.gif)
