# Use the latest Ubuntu image
FROM ubuntu:latest

# Update and install required packages
RUN apt-get update && apt-get install -y sudo openssh-server curl && apt-get clean

# Create the user 'testuser' and add it to the sudo group
RUN useradd -ms /bin/bash testuser && \
    echo 'testuser:password' | chpasswd && \
    usermod -aG sudo testuser

# Allow passwordless sudo for the user
RUN echo 'testuser ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers

# Set up SSH service
RUN mkdir /var/run/sshd && \
    sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/' /etc/ssh/sshd_config && \
    echo 'PasswordAuthentication yes' >> /etc/ssh/sshd_config

# Define a build argument for the SSH key URL (defaulting to your GitHub keys)
ARG SSH_KEY_URL=https://github.com/jasperf.keys

# Add your SSH public key from the specified URL to the authorized keys for testuser
RUN mkdir -p /home/testuser/.ssh && \
    curl -s ${SSH_KEY_URL} -o /home/testuser/.ssh/authorized_keys && \
    chmod 600 /home/testuser/.ssh/authorized_keys && \
    chown -R testuser:testuser /home/testuser/.ssh

# Expose port 22 for SSH
EXPOSE 22

# Start SSH service
CMD ["/usr/sbin/sshd", "-D"]
