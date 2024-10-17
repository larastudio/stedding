# Stedding

Stedding is a minimalistic LEMP Stack setup for Laravel PHP. It facilitates the setting up of Laravel apps on a well prepared Ubuntu based VPS using Ansible Playbooks.

## Quick Setup

To get started, you will need:

- **Ansible Control Node**: A machine with Ansible installed and configured to connect to your Ansible hosts using SSH keys.
- **Ansible Hosts**: One or more remote Ubuntu 22.04 servers. Ensure that each host has the control node’s public key added to its `authorized_keys` file for SSH access.

**NB** For hashing the password for the admin user you have to install passlib:

```bash
pip install passlib
```

### Steps:

1. **Install Ansible**: Ensure Ansible is installed on your control node. You can follow this [Ansible installation guide](https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-ansible-on-ubuntu-18-04).

2. **Clone this repository**:

   ```bash
   git clone https://github.com/your-repository/stedding.git
   cd stedding
   ```

3. **Set up your inventory file**: Use `inventory-example` as a base for creating your own `inventory` file.

4. **Adjust configuration**: Modify the values in your `group_vars/all.yml` file according to your environment.

5. **Run the server setup playbook**: Execute the `server-setup.yml` playbook to set up the LEMP server:

   ```bash
   ansible-playbook server-setup.yml
   ```

6. **Deploy Laravel**: Run the `laravel-deploy.yml` playbook to deploy the demo Laravel application:

   ```bash
   ansible-playbook laravel-deploy.yml
   ```

7. **Access the Application**: Use your server's IP address or hostname to verify the setup.

---

## Local Testing with Docker

You can use Docker to create an isolated environment for running your Ansible playbooks locally. You will need to
run the playbook twice most of the time due to network issues with ipv.

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
    You can then check Ubuntu version
    ```bash
    sudo su
    root@70e2eb742cab:/home/testuser# cd /etc/os-release
    bash: cd: /etc/os-release: Not a directory
    root@70e2eb742cab:/home/testuser# cat /etc/os-release
    PRETTY_NAME="Ubuntu 24.04 LTS"
    ...
    UBUNTU_CODENAME=noble
    LOGO=ubuntu-logo
    ```
5. Run local playbook 
   ```bash
   ansible-playbook  -i inventory server-setup.yml --limit local
   ```
## Lima

You can also test the playbook with Lima VM. General setup instructions:
```bash
brew install lima
limactl create --arch=x86_64 template://ubuntu
limactl edit ubuntu
```
and add
```bash
ssh:
  localPort: 2022
portForwards:
  - guestPort: 80
    hostPort: 8080
  - guestPort: 443
    hostPort: 8443
```
at the end of the file and save that. Lima will ask you to start the system and say yes. Will take a bit of time.

then do a 
```bash
sudo nano ~/.config/ssh_config
```
and add
```bash
host lima-ubuntu
  HostName localhost
  Port 2022
```
useful for VS Code access. Then to test shell access to virtual iamge do:
```bash
ssh jasperfrumau@127.0.0.1 -p 2022
```
Also adjust `inventory line:
```bash
[lima]
ansible_host=127.0.0.1 ansible_port=2022 ansible_user=yourlocaluser ansible_become=yes
```
and add your own local user you use. To now run playbook you can now use 
```bash
ansible-playbook  -i inventory server-setup.yml --limit lima
```

**NB** see `lima.yml` in root project where we have the full Ubuntu lima config file with changes.

## Notes

This is the new version of Stedding, based on [Heidi's Ansible Laravel Demo](https://github.com/do-community/ansible-laravel-demo).
