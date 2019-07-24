# Stedding
Stedding is a minimalistic LEMP Stack setup for [Laravel PHP](https://laravel.com/). It facilitates the setting up of Laravel apps on a well prepared [Ubuntu based](https://www.ubuntu.com/) VPS using Ansible Playbooks. 


## Local Box Requirements
You need to have Ansible installed on your local computer. This really differs from box to box See [Ansible Documents](http://docs.ansible.com/ansible/intro_installation.html) for instructions.

For hashing the password for the admin user you have to install passlib:
````
pip install passlib
````

## Remote Server Requirements
To run Ansible Playbooks properly on *Ubuntu 17.10+* we need to setup a sudo user and make sure Python and some other packages such as `ppa:ondrej/php` are available so Ansible can run. The setting up of a sudo user and adding of the SSH keys has been taken care of. So is the adding of Python and Ondrej's PHP PPA. All you need is root access to the Ubuntu 16.0.4 box. Preferably using an SSH key.

**NB** [Gist with useful setup tips](https://gist.github.com/jasperf/0be4439bbda9a324dd24e7300f357eb4)

## Playbooks

* Install prerequisites
* Sudo user Creation
* Web user Creation
* LEMP Provisioning
* Laravel Homebase Setup

## Roles

Geerllingguy Roles:
* [nginx](https://github.com/geerlingguy/ansible-role-nginx)
* [certbot](https://github.com/geerlingguy/ansible-role-certbot)
* [php](https://github.com/geerlingguy/ansible-role-php)
* [mysql](https://github.com/geerlingguy/ansible-role-mysql)
* [php-mysql](https://github.com/geerlingguy/ansible-role-php-mysql)
* [memcached](https://github.com/geerlingguy/ansible-role-memcached)
* [git](https://github.com/geerlingguy/ansible-role-git)
* [composer](https://github.com/geerlingguy/ansible-role-composer)
* [node](https://github.com/geerlingguy/ansible-role-nodejs)

added where possible with `ansible-galaxy install --roles-path . geerlingguy.rolename` inside roles folder.

Other roles:
* [Ansible Deployer](https://github.com/jverdeyen/ansible-deployer-in) by jverdeyen
* [Ansible Swapfile](https://github.com/kamaln7/ansible-swapfile) by Kamaln7

## Stedding Variables
Do not forget to adjust the vars in:

* `group_vars/all` 
* `vars/mainyml` 
* `hosts`

where need be. 

example for the `all` file (not added in repo):
````
repo_url : git@github.com:Larastudio/larastudio.git
github_keys: https://github.com/jasperf.keys
git_branch: master
sudo_user: admin
web_user: web
upassword: passsword
domain: larastud.io
database_name: database_name
database_user: database_user
database_user_password: database_user_password
````
Not all will have to be adjusted perhaps but some will have to. This is besides the addition of the hosts file. Here you normally add at least the ip address for the server:
````
[web]
xxx.xxx.xx.xxx
````
The variables in `vars/main.yml` are for setting up PHP, MySQL and Nginx details based on Geerlingguy roles. The variables in `group_vars/all` are for the repo, keys, branch, user, password and domain.


### Nginx
Nginx details are stored in `vars/main.yml` and `server.yml` . One host for the site being used for testing purposes has been added there. Do change it to work with the domain of your choice.

### Certbot
Using Geerling's [Certbot role](https://github.com/geerlingguy/ansible-role-certbot) Let's Encrypt's [Certbot](https://certbot.eff.org/) has been added to the server. This allows the site to use Let's Encrypt SSL certificate. This does however not adjust the Nginx's domain configuration to server on 443 and redirect port 80 traffic to port 443. Tweaks for this are being made.

Nginx Certbot plugin has to be added using
````
sudo apt-get install python-certbot-nginx
````
A task is in the works, but not done.
Then you can run:
````
certbot --nginx
````
to start the installation. You will then be asked to choose a domain. Next, they will ask you to agree with the TOS and install all. Working on an incorporation on the server still.

*NB* May not be necessary if you run your own certs only. See further down on SSL
### PHP

To work with PHP 7.1. Ondrej's PHP PPA is added in requirements playbook using:
````
- name: Add repository for PHP 7.
      apt_repository: repo='ppa:ondrej/php'
````

#### PHP OpCache

For pre compiling PHP scripts Stedding uses PHP OpCache. For quick emptying OpCache use `/etc/init.d/php7.1-fpm restart` . Read more on it at [Ma.ttias.be](https://ma.ttias.be/how-to-clear-php-opcache/)

### Memcached
"Free & open source, high-performance, distributed memory object caching system, generic in nature, but intended for use in speeding up dynamic web applications by alleviating database load."

### MariaDB

The MariaDB details are added to `vars/main.yml` are just dummy data. Do adjust them.

### Composer

Composer is added and binary is put in the directory of the web user. Laravel is also added as a globally required package so it can be used.

### Mail
To set up your Laravel application to work with [Mailgun](https://www.mailgun.com/) for sending out emails which is used in this repo check out this [Laravel document](https://laravel.com/docs/mail) 
### Nodejs
Nodejs role is installed and we automatically add the following global packages:

````
nodejs_npm_global_packages:
  - name: yarn
  - name: bower
  - name: grunt
  - name: gulp
````

Bower and Grunt will probably be removed in the future.

## Laravel Homebase Setup

To run your Laravel application from a specific project directory, the one added to your Nginx configuration, we have added a separate playbook. One we will expand upon soon with other tasks. For now the project directory is created only using this task:
```
  - name: Project Folder Creation
    file: dest=/var/www/{{domain}} mode=2755 state=directory owner=web group=www-data
````
The domain can be set in `group_vars/all`. [GUID]

## Deployment
Deployment script using [Deployer.org](https://deployer.org/) has been added as a role to this Ansible package. It is using the latest role version that is available on Github.  The repository with the deploy.php script that has been tested with the Laravel app Larastudio can be found [here](https://github.com/jasperf/larastudio). 

## Swapfile
Kamal's [swapfile role](https://github.com/kamaln7/ansible-swapfile) has been added with default configuration. This to add some more RAM in the form of a swapfile which is especially useful when you are using a 512MB Droplet at Digital Ocean for example.

## Let's Encryp or Commercial SSL Certificates

OpenSSL role has been added so self signed certificates can be added when you would like to. Current Stedding setup is aimed at working with Let's Encrypt so this role has not been acitvated. The path to own SSL certificates have been commented out.

As you will see there are two server blocks. One is for port 80, the second one should be for port 443 and both in different files. Let's Encrypt task for auto renewal has also been added.
