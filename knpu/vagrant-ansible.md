# Vagrant <3's Ansible

Our first big goal is to see if we can use Ansible to setup, or provision, an entire
server that can run our MooTube app. To setup an empty VM, we'll use Vagrant
with VirtualBox.

So before we start, make sure that you have VirtualBox installed: it's different
for each OS, but should - hopefully - be pretty easy. Next, make sure you have Vagrant:
it has a nice installer for every OS. On my Mac, surprise! I installed Vagrant via
brew.

As long as you can type: 

```terminal
vagrant -v
```

you're good to go.

## Vagrantfile Setup!

If you're new to Vagrant, it's a tool that helps create and boot different virtual
machines, usually via VirtualBox behind the scenes. It works by reading a configuration
file... which we don't have yet. Let's generate it!

```terminal
vagrant init ubuntu/trusty64
```

That just created a file - `Vagrantfile` - at the root of our project that will boot
a VM using an `ubuntu/trusty64` image. That's not the newest version of Ubuntu, but
it works really well. You're free to use a different one, but a few commands or usernames
and passwords might be different!

***TIP
If you want to use the latest Ubuntu 18.04 LTS release, you'll need a few tweaks:

1) Change the VM box in `Vagrantfile` to:

```ruby
# Vagrantfile
Vagrant.configure("2") do |config|
  # ...
  config.vm.box = "ubuntu/bionic64"
  # ...
```

Or `ubuntu/xenial64` in case you're interested in Ubuntu 16.04 LTS release.

2) Ubuntu 18.04/16.04 requires a private SSH key to be specified instead of a simple
password to login via SSH - Ansible can't log in into the server using just a username and
password pair. Also, the Ansible user should be set to `vagrant`. You can specify all this
information in the `hosts.ini` file for the VirtualBox host:

```ini
# ...
[vb]
192.168.33.10 ansible_user=vagrant ansible_ssh_private_key_file=./.vagrant/machines/default/virtualbox/private_key
# ...
```

Make sure to uncomment `private_network` configuration as we did below in [this code block](#codeblock-efdace2f14)
to be able to connect to the `192.168.33.10` IP.

3) Notice, that Ubuntu 18.04/16.04 has the new pre-installed Python 3. In case you have an error related
to Python interpreter, specify the path to its binary explicitly as:

```ini
# ...
[vb]
192.168.33.10 ansible_user=vagrant ansible_ssh_private_key_file=./.vagrant/machines/default/virtualbox/private_key ansible_python_interpreter=/usr/bin/python3
# ...
```

4) Ubuntu 18.04/16.04 doesn't come with `aptitude` pre-installed, so you will need to install it first
if you want to use the `safe` upgrade option for installed packages - we will talk about it later
in this course. Just add one new task to your playbook before upgrading:

```yaml
# ansible/playbook.yml
---
- hosts: vb
  # ...
  tasks:
    # ...
    - name: Install aptitude
      become: true
      apt:
        name: aptitude

    - name: Upgrade installed packages
      become: true
      apt:
        upgrade: safe
    # ...
```
***

## Boot that VM!

With that file in place, let's boot the VM!

```terminal
vagrant up
```

Then... go make a sandwich! Or run around outside! Unless you've run this command
before, it'll need to download the Ubuntu image... which is pretty huge. So go freshen
up your cup of coffee and come back.

Thanks to the power of video, we'll zoom to the end! Zooooom!

When it finishes, make sure you can SSH into it:

```terminal
vagrant ssh
```

With any luck, you'll step right into your brand new, basically empty, but totally
awesome, Ubuntu virtual machine.

By the way, Vagrant stores some info in a `.vagrant` directory. In a real project,
you'll probably want to add this to your `.gitignore` file:

[[[ code('e384a4a614') ]]]

## Setup an External IP Address

Our goal is to have Ansible talk to this new VM. For that, we need a dependable IP
address for the VM. Check out the `Vagrantfile` that was generated automatically
for us: it has a section about a "private network". Uncomment that!

[[[ code('efdace2f14') ]]]

This will let us talk to the VM via `192.168.33.10`.

For that to take effect, run:

```terminal
vagrant reload
```

***TIP
If you're inside the VM, for stepping out of it simply run:

```terminal-silent
exit
```
***

That should take just a minute or two. Perfect! And now we can ping that IP!

```terminal
ping 192.168.33.10
```

## Configuring the new Ansible Host

The VM represents a new host. And that means we need to add it to our hosts file!
In `hosts.ini`, let's keep the `local` group and add another called `vb`, for VirtualBox.
Under there, add the IP: `192.168.33.10`:

[[[ code('a46814d8af') ]]]

We know that as soon as we make this change, we should be able to use the `vb` host:

```terminal
ansible vb -m ping -i ansible/hosts.ini
```

That should work, right? It fails! The ping module does a bit more than just a ping,
and in this case, it's detecting that Ansible can't SSH into the machine. The reason
is that we haven't specified a username and password or key to use for SSH.

## Configuring SSH Properly

To see what I mean, try SSH'ing manually - the machine is setup with a `vagrant` user:

```terminal
ssh vagrant@192.168.33.10
```

Woh! Our first error looks awesome!

> WARNING: REMOTE HOST IDENTIFICATION HAS CHANGED!

You may or may not get this error. Since I've used Vagrant in this same way in the
past, it's telling me that last time I SSH'ed to this IP address, it was a different
machine! We know that's ok - there's nothing nefarious happening. To fix it, I just
need to find line 210 of my `known_hosts` file and remove the old fingerprint.
I'll do that and save. Try it again:

```terminal
ssh vagrant@192.168.33.10
```

It saves the fingerprint and then asks for a password. The password for the image
that we're using is `vagrant`. That's pretty standard, but it might be different
if you're using a different image.

***TIP
If you still see an error like:

> vagrant@192.168.33.10: Permission denied (publickey).

It seems like the server requires a private SSH key file to be specified. Try to specify
it as an identity file:

```terminal-silent
ssh vagrant@192.168.33.10 -i ./.vagrant/machines/default/virtualbox/private_key
```
***

We're inside! So, how can we tell Ansible to SSH with username `vagrant` and password
`vagrant`? The answer is... not surprising! These are two more variables in your
hosts inventory file: `ansible_user` set to `vagrant` and `ansible_ssh_pass=vagrant`:

[[[ code('ce698677df') ]]]

Try the ping again:

```terminal
ansible vb -m ping -i ansible/hosts.ini
```

***TIP
If you still can't SSH into the Vagrant box with Ansible using a simple username/password pair
and continue getting an error like:

> 192.168.33.10 | FAILED! => {
>     "msg": "to use the 'ssh' connection type with passwords, you must install the sshpass program"
> }

Try to specify the SSH private key instead of password. For this, change the line to:

```ini
# ...
[vb]
192.168.33.10 ansible_user=vagrant ansible_ssh_private_key_file=./.vagrant/machines/default/virtualbox/private_key
# ...
```
***

Eureka! But, quick note about the SSH password. If this weren't just a local VM,
we might not want to store the password in plain text. Instead, you can use a private
key for authentication, or use the Ansible "vault" - a cool feature that lets us
*encrypt* secret things, like passwords. More on that later.

But for now, our setup is done! We have a VM, and Ansible can talk to it. Next, we
need to create a *playbook* that's capable of setting up the VM.
