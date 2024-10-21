# Stedding

<p align="center">
  <a href="https://imagewize.com">
    <img alt="Cafe JP Coen" src="./stedding-logo-v5.jpg" height="150" >
  </a>
</p>

<p align="center">Stedding is a minimalistic LEMP Stack setup for Laravel PHP. It facilitates the setting up of Laravel apps on a well prepared Ubuntu based VPS using Ansible Playbooks.
</p>


## Table of Contents

- [Provisioning and Resizing a VPS at Hetzner with Ansible](#provisioning-and-resizing-a-vps-at-hetzner-with-ansible)
  - [Prerequisites](#prerequisites)
  - [Configuration](#configuration)
    - [Hetzner API Token](#hetzner-api-token)
    - [VPS Variables](#vps-variables)
    - [SSH Key](#ssh-key)
  - [Provisioning the VPS](#provisioning-the-vps)
  - [Resizing the VPS](#resizing-the-vps)
  - [Additional Notes](#additional-notes)
- [Server Setup](#server-setup)
  - [Laravel Application](#laravel-application)
  - [Ansible](#ansible)
  - [Repository Copy](#repository-copy)
  - [Set up your inventory file](#set-up-your-inventory-file)
  - [Variables](#variables)
  - [Run Server setup playbook](#run-server-setup-playbook)
- [Certbot for SSL Certificates](#certbot-for-ssl-certificates)
  - [DNS or HTTP Validation](#dns-or-http-validation)
  - [Environment-Specific Configuration](#environment-specific-configuration)
  - [Running the Playbook for Specific Environments](#running-the-playbook-for-specific-environments)
- [Deploy Laravel](#deploy-laravel)
- [Docker](#docker)
  - [Steps to Set Up](#steps-to-set-up)
- [Lima Virtual Machine](#lima-vm)
  - [Lima SSH and HTTP Ports](lima-ssh-and-http-ports)
  - [Starting Lima and SSH Config](starting-lima-and-ssh-onfig)
- [Notes](#notes)


## Provisioning and Resizing a VPS at Hetzner with Ansible

This section explains how to provision and resize a VPS on Hetzner Cloud using Ansible and the Hetzner Cloud API. If you already 
have a VPS set up or you are using another provider you can skip this part.

### Prerequisites

Ensure you have:
- A Hetzner Cloud API token (stored in `files/hetzner.ini`)
- Ansible installed
- The `hcloud` Python package for managing Hetzner Cloud resources which you install running this playbook for the first time.

### Configuration

1. **Hetzner API Token**:  
   Store your API token in `files/hetzner.ini`:

    ```ini
    dns_hetzner_api_token = your-hetzner-api-token
    ```

2. **VPS Variables**:  
   Define your VPS name and related settings in `group_vars/all.yml`:

    ```yaml
    vps_name: "my-vps-server"
    ```

3. **SSH Key**:  
   Ensure the SSH key is configured in Hetzner and referenced by name in the playbook.

   You would have previously uploaded your public SSH key to Hetzner, which is stored under a specific name in your Hetzner Cloud account (e.g., `my-ssh-key`) at `https://console.hetzner.cloud/projects/xxxxx/security/sshkeys`.

### Provisioning the VPS

To provision a new VPS on Hetzner Cloud, run the following playbook:

```bash
ansible-playbook hetzner-vps-provisioning.yml
```

This will create a new VPS with the specified configuration, such as server type, location, and SSH keys.

### Resizing the VPS

To resize an existing VPS (e.g., from `CX21` to `CX31`), use the resizing playbook:

```bash
ansible-playbook hetzner-vps-resizing.yml
```

This playbook will:
- Stop the VPS
- Resize it to the desired server type
- Start the VPS again

### Additional Notes

- **Disk Resizing**: After resizing, you may need to manually resize the filesystem to utilize the additional disk space.
- **Limitations**: Hetzner does not support downgrading instance types.

For provisioning, refer to `hetzner-vps-setup.yml`, and for resizing, use `hetzner-vps-resizing.yml`.


## Server Setup

To get started, you will need:

- **Ansible Control Node**: A machine with Ansible installed and configured to connect to your Ansible hosts using SSH keys.
- **Ansible Hosts**: One or more remote Ubuntu 24.04 servers. Ensure that each host has the control node’s public key added to its `authorized_keys` file for SSH access.


### Laravel Application

To work with the Laravel Application use the git submodule command to add the app to the directory application. Here is the 
command to add the Laratudio Arbor Application, but you can replace it by yours:
```bash
cd application
git submodule add git@github.com:larastudio/arbor.git
```
### Ansible

Ensure Ansible is installed on your control node. You can follow this [Ansible installation guide](https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-ansible-on-ubuntu-18-04).

### Repository Copy

Clone this repository:

   ```bash
   git clone https://github.com/your-repository/stedding.git
   cd stedding
   ```

### Set up your inventory file:

Use `inventory-example` as a base for creating your own `inventory` file. Add hosts as needed.

### Variables 

Modify the values in your `group_vars/all.yml` , `groups_vars/lima.yml` , `groups_vars/docker.yml` or `groups_vars/production.yml` according to your environment.

### Run Server setup playbook

Execute the `server-setup.yml` playbook to set up the LEMP server:

   ```bash
   ansible-playbook -i inventory server-setup.yml
   ```

You can add `--limit host` where host is `lima`, `docker`, `staging` or `production` depending on the host you are going for. 


## Certbot for SSL Certificates

Stedding supports SSL certificate issuance via Let's Encrypt using Certbot. You can choose between DNS-based validation (for wildcard certificates) and HTTP-based validation.

### DNS or HTTP Validation

To use DNS validation for obtaining wildcard SSL certificates, set the `certbot_dns` variable to `true` in your `group_vars/all.yml` or a specific environment file. You'll also need to specify the DNS provider in the `dns_provider` variable (e.g., `transip`, `hetzner`).

Example for DNS validation in `group_vars/all.yml`:
```yaml
certbot_dns: true
dns_provider: "transip"  # Use "hetzner" for Hetzner DNS
certbot_email: "your-email@example.com"
```

To use HTTP validation, set `certbot_dns` to `false`:
```yaml
certbot_dns: false
```

### Environment-Specific Configuration

For production and staging environments, make sure to add a fully qualified domain name (FQDN) in `group_vars/production.yml` or `group_vars/staging.yml`. Certbot requires the FQDN for both DNS and HTTP validation.

Example for `group_vars/production.yml`:
```yaml
http_host: "example.com"
```

For testing environments (e.g., Lima or Docker), you can set the `http_host` to a local domain or IP address.

### Running the Playbook for Specific Environments

When running the playbook, you can limit the execution to specific environments like production or staging by using the `--limit` flag. This ensures that environment-specific configurations, including Certbot, are applied only to the relevant hosts.

Example for running the playbook in production:
```bash
ansible-playbook server-setup.yml --limit production
```

For staging:
```bash
ansible-playbook server-setup.yml --limit staging
```

Make sure you have a `group_vars/production.yml` or `group_vars/staging.yml` file set up with the necessary configuration values, such as the FQDN (`http_host`).

## Deploy Laravel

Run the `laravel-deploy.yml` playbook to deploy the demo Laravel application:

   ```bash
   ansible-playbook laravel-deploy.yml
   ```
   
   You do need to have the application added to the application directory. You can add the application by copying over data or adding it as a submodule.

### Access the Application**: Use your server's IP address or hostname to verify the setup.

## Docker 

You can use Docker to create an isolated environment for running your Ansible playbooks locally. You will need to run the playbook twice most of the time due to network issues with ipv.

### Steps to Set Up:

1. **Install Docker**: Follow the [Docker installation guide](https://docs.docker.com/engine/install/).

2. **Build and Run the Docker Container**:

    Update `SSH_KEY_URL` with your Github url for ssh public keys. Then run the command to build the image
   ```bash
   docker build -t ansible-test-host .
   ```

3. Run the Docker Container: After building the image, start the container:

    ```bash
    docker run --privileged  -d --name ansible-test-host -p 2222:22 ansible-test-host
    ```

   This will run the container in detached mode and bind the container's SSH service to port 2222 on your local machine.
   
   **NB** The `--privileged` flag grants the container extended privileges, allowing it to modify networking settings like UFW and iptables.

4.  SSH into the Container (Optional): 
You can now SSH into the container using the testuser account to verify that everything is working:

    ```bash
    ssh testuser@localhost -p 2222
    ```

    You can then check Ubuntu version:

    ```bash
    sudo su
    root@70e2eb742cab:/home/testuser# cat /etc/os-release
    PRETTY_NAME="Ubuntu 24.04 LTS"
    UBUNTU

_CODENAME=noble
    LOGO=ubuntu-logo
    ```

5. Run local playbook:

   ```bash
   ansible-playbook  -i inventory server-setup.yml --limit local
   ```

## Lima VM

You can also test the playbook with Lima VM. General setup instructions:

```bash
brew install lima
limactl create --arch=x86_64 template://ubuntu
```

### Lima SSH and HTTP Ports

Choose edit to make adjusts and add the following to the configuration file:

```yaml
ssh:
  localPort: 2022
portForwards:
  - guestPort: 80
    hostPort: 8080
  - guestPort: 443
    hostPort: 8443
  - guestPort: 6379  # Redis port in the guest VM
    hostPort: 6380   # Forward to port 6380 on your local machine
```

### Starting Lima and SSH Config

Then, save and start the system when prompted using `limactl start ubuntu`

Next, edit your SSH config:

```bash
sudo nano ~/.config/ssh_config
```

Add the following:

```bash
host lima-ubuntu
  HostName localhost
  Port 2022
```

To access the virtual image via shell, run:

```bash
ssh yourusername@127.0.0.1 -p 2022
uname -a
ss -tuln
```

or one of these two
```bash
ssh warden@127.0.0.1 -p 2022
limactl shell ubuntu
```

to get into the vm and then run the commands.

To test Redis port forwarding from localhost:
```bash
redis-cli -h 127.0.0.1 -p 6380
```

Update your `inventory` file:

```ini
[lima]
ansible_host=127.0.0.1 ansible_port=2022 ansible_user=yourlocaluser ansible_become=yes
```

Run the playbook:

```bash
ansible-playbook -i inventory server-setup.yml --limit lima
```

once done you can run the deployment of the application:

```bash
ansible-playbook -i inventory laravel-deploy.yml --limit lima
```

Do not forget to update host's `/etc/hosts` and add `127.0.0.1 arbor.local` or add name as added for `http_host` . Will be able to reach site using `http://arbor.local:8080`

**NB**: See `lima.yml` in the root project folder for the full configuration.

## Notes

This is the new version of Stedding, based on [Heidi's Ansible Laravel Demo](https://github.com/do-community/ansible-laravel-demo).
