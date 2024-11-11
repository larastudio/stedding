# Stedding

<p align="center">
  <a href="https://imagewize.com">
    <picture>
      <!-- Dark Mode Logo -->
      <source srcset="./stedding-logo-light.png" media="(prefers-color-scheme: dark)">
      <!-- Light Mode Logo -->
      <img src="./stedding-logo-dark.png" alt="Stedding Logo" height="250">
    </picture>
  </a>
</p>

<p align="center">Stedding - Ansible Stack for Laravel</p>

## Introduction

Stedding is a minimalistic LEMP Stack setup for Laravel PHP. It facilitates the setting up of Laravel apps on a well-prepared Ubuntu-based VPS using Ansible Playbooks.

## Table of Contents

- [Introduction](#introduction)
- [Local Development](#local-development)
  - [Docker](#docker)
    - [Steps to Set Up](#steps-to-set-up)
  - [Lima VM](#lima-vm)
    - [Lima SSH and HTTP Ports](#lima-ssh-and-http-ports)
    - [Starting Lima and SSH Config](#starting-lima-and-ssh-config)
    - [Self Signed SSL](#self-signed-ssl)
- [Provisioning](#provisioning)
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
  - [Run Server Setup Playbook](#run-server-setup-playbook)
- [Deploy Laravel](#deploy-laravel)
- [Certbot](#certbot)
  - [DNS or HTTP Validation](#dns-or-http-validation)
  - [Environment-Specific Configuration](#environment-specific-configuration)
  - [Running the Playbook for Specific Environments](#running-the-playbook-for-specific-environments)
- [Notes](#notes)

## Local Development

Local development can be done in many ways. We currently have implemented Docker and Lima Virtual Machine solutions. We may drop Docker at a later stage as we prefer Lima Virtual Machine. Lima is lightweight, runs well on macOS as well as other operating systems, and allows for a full Ubuntu setup with all necessary packages.

### Docker

You can use Docker to create an isolated environment for running your Ansible playbooks locally. You may need to run the playbook twice due to network issues with IPv6.

#### Steps to Set Up

1. **Install Docker**: Follow the [Docker installation guide](https://docs.docker.com/engine/install/).

2. **Build the Docker Image**:

    Update `SSH_KEY_URL` in your `Dockerfile` with your GitHub URL for SSH public keys. Then run the command to build the image:

   ```bash
   docker build -t ansible-test-host .
   ```

3. **Run the Docker Container**:

    After building the image, start the container:

    ```bash
    docker run --privileged -d --name ansible-test-host -p 2222:22 ansible-test-host
    ```

   This will run the container in detached mode and bind the container's SSH service to port 2222 on your local machine.

   **Note**: The `--privileged` flag grants the container extended privileges, allowing it to modify networking settings like UFW and iptables.

4. **SSH into the Container (Optional)**:

    You can now SSH into the container using the `testuser` account to verify that everything is working:

    ```bash
    ssh testuser@localhost -p 2222
    ```

    You can then check the Ubuntu version:

    ```bash
    sudo su
    root@container-id:/home/testuser# cat /etc/os-release
    PRETTY_NAME="Ubuntu 24.04 LTS"
    UBUNTU_CODENAME=noble
    LOGO=ubuntu-logo
    ```

5. **Run the Local Playbook**:

   ```bash
   ansible-playbook -i inventory server-setup.yml --limit docker
   ```

### Lima VM

You can use the playbook on your Lima VM. To start your Ubuntu image it first needs to be created:

```bash
brew install lima
limactl create --arch=x86_64 template://ubuntu
```

If you prefer to use the latest virutalization technology on your Mac use
```bash
limactl create --arch=aarch64 --vm-type=vz --mount-type=virtiofs template://ubuntu
```

We currently however seem to be having issues with port forwarding using this version.

#### Lima SSH and HTTP Ports

Choose to edit the configuration and add the following to the configuration file:

```yaml
ssh:
  localPort: 2022
portForwards:
  - guestPort: 80
    hostPort: 8080
  - guestPort: 443
    hostPort: 8443
  - guestPort: 6379
    hostPort: 6380
```

#### Starting Lima and SSH Config

Then, save and start the system when prompted using:

```bash
limactl start ubuntu
```

Next, edit your SSH config:

```bash
nano ~/.ssh/config
```

Add the following:

```ssh
Host lima-ubuntu
  HostName localhost
  Port 2022
  User yourlocaluser
  IdentityFile ~/.ssh/id_rsa
```

To access the virtual machine via shell, run:

```bash
ssh -p 2022 mac-user-name@127.0.0.1
```
or
```bash
ssh jasperfrumau@lima-ubuntu -p 2022
```
with thhe `~/.ssh/config` updated.  Once entered you can double check you are in the right environment with the right ports opened using:
```bash
uname -a
ss -tuln
```

Alternatively, you can use:

```bash
limactl shell ubuntu
```

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

Once done, you can run the deployment of the application:

```bash
ansible-playbook -i inventory laravel-deploy.yml --limit lima
```

Do not forget to update your host's `/etc/hosts` file and add:

```
127.0.0.1       arbor.local
::1             arbor.local
```

or the name as specified in `http_host`. You will be able to reach the site using `http://arbor.local:8080`

### Self Signed SSL
To have a secure local Lima VM you need to run
```bash
ansible-playbook -i inventory lima-ssl.yml --ask-become-pass
```

You can test certificates set using
```bash
openssl s_client -connect arbor.local:8443 -showcerts
```
or
```bash
curl -v https://arbor.local:8443
```

Do change domain if need be.

## Provisioning

This section explains how to provision and resize a VPS on Hetzner Cloud using Ansible and the Hetzner Cloud API. If you already have a VPS set up or you are using another provider, you can skip this part.

### Prerequisites

Ensure you have:

- A Hetzner Cloud API token (stored in `files/hetzner.ini`)
- Ansible installed
- The `hcloud` Python package for managing Hetzner Cloud resources (installed when running the playbook for the first time)

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

   You should have previously uploaded your public SSH key to Hetzner, which is stored under a specific name in your Hetzner Cloud account (e.g., `my-ssh-key`) at `https://console.hetzner.cloud/projects/xxxxx/security/sshkeys`.

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

For provisioning, refer to `hetzner-vps-provisioning.yml`, and for resizing, use `hetzner-vps-resizing.yml`.

## Server Setup

To get started, you will need:

- **Ansible Control Node**: A machine with Ansible installed and configured to connect to your Ansible hosts using SSH keys.
- **Ansible Hosts**: One or more remote Ubuntu 24.04 servers. Ensure that each host has the control node’s public key added to its `authorized_keys` file for SSH access.

### Laravel Application

To work with the Laravel application, use the git submodule command to add the app to the `application` directory. Here is the command to add the Larastudio Arbor Application, but you can replace it with your own:

```bash
cd application
git submodule add git@github.com:larastudio/arbor.git
```

### Ansible

Ensure Ansible is installed on your control node. You can follow this [Ansible installation guide](https://docs.ansible.com/ansible/latest/installation_guide/index.html).

### Repository Copy

Clone this repository:

```bash
git clone https://github.com/your-repository/stedding.git
cd stedding
```

### Set up your inventory file

Use `inventory-example` as a base for creating your own `inventory` file. Add hosts as needed.

### Variables

Modify the values in your `group_vars/all.yml`, `group_vars/lima.yml`, `group_vars/docker.yml`, or `group_vars/production.yml` according to your environment.

### Run Server Setup Playbook

Execute the `server-setup.yml` playbook to set up the LEMP server:

```bash
ansible-playbook -i inventory server-setup.yml
```

You can add `--limit host`, where `host` is `lima`, `docker`, `staging`, or `production` depending on the host you are targeting.

## Deploy Laravel

Run the `laravel-deploy.yml` playbook to deploy the Laravel application:

```bash
ansible-playbook -i inventory laravel-deploy.yml
```

You need to have the application added to the `application` directory. You can add the application by copying over data or adding it as a submodule.

### Access the Application

Use your server's IP address or hostname to verify the setup.

**Note**: See `lima.yml` in the root project folder for the full configuration.

## Certbot

Stedding supports SSL certificate issuance via Let's Encrypt using Certbot. You can choose between DNS-based validation (for wildcard certificates) and HTTP-based validation. This part of the playbook is still under development. HTTP to HTTPS redirection needs to be implemented in the Nginx site configuration. This role also needs a flag for enabling or disabling it, as it needs to be run when the server has been set up and the domain name is pointing to the server.

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

Example in `group_vars/production.yml`:

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

## Notes

This is the new version of Stedding, based on [Heidi's Ansible Laravel Demo](https://github.com/do-community/ansible-laravel-demo).
