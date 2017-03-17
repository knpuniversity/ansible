# Vagrant Ansible

So our first task, and main goal, is going to be to see if we can create a really nice Ansible setup that can provision an entire server for us. And to do that we are actually going to use virtual mosk and Vagrant to create a virtual machine. And then we are entirely going to tap Ansible, install everything needed on that machine to get our application up and running.

So before we start, make sure that you have VirtualBox installed. However that's done on your particular machine. And then also, Vagrant, which has a nice installer for every system. On Mac I actually use Brew to install Vagrant, but you can install it however you want. As long as, ultimately you can type Vagrant dash V and get the version of your Vagrant.

So in order to use Vagrant, we need to have a Vagrant file that describes what we want our virtual machine to look like. So we are going to generate one by using Vagrant init ubuntu/trusty 64. That's not the newest version of Ubuntu, but it's one that works really well. And you're free to use a different one. But if you do, there might be subtle things you need to update for inside, while you are following along with the tutorial.

Once you've done that, you should be able to type Vagrant up and wait for the magic to happen. If this is the first time running the command, it probably will have to download a giant image which might take awhile so apologies. Run this. Come back once that's done downloading, and let it do it's thing.

Once that's done, you should be able to test it out by doing Vagrant SSH. And hopefully that will long you into your brand new, basically empty Ubuntu virtual machine.

By the way, this does store some configuration in a .Vagrant file, which in the real project you are actually going to want to add to your .gitignore file.

Cool. So our goal is to have Ansible talk to our new virtual machine. Now by default it doesn't actually have an external IP address that we can reach it via. But if you look at the Vagrant file which was generated automatically for us when we typed Vagrant init, there is a section here about a private network. So go ahead and uncomment that out. This is going to allow us on our host machine to access the virtual machine via this IP address.

Once you've done that, you're actually going to need to type Vagrant reload so that it can make the changes on the server so that that network is available.

Perfect. If you that IP address, you should be able to ping that and it's actually coming back. So now that we want to talk to this host, it means we are going to go into our host file and add it here. Now I'm going to keep my local group and create a new group called VB for VirtualBox. And under there, I'm going to add our IP address. Of course, we know as soon as we do that, we should be able to come over here at Ansible VB -M ping, to try to use the ping module, -i ansible/hosts.ini.

When you do that, that's not going to work. The reason is that we haven't specified any username or password for that vagrant machine. And the vagrant machine isn't set up to automatically allow us to have access without a username password. So actually if you tried, SSH Vagrant@192.168.33.10 you're going to get one, if not two, errors. If this is your first time using Vagrant, there's a good chance you won't see this error. But if you have used Vagrant before, what it's basically telling me is I've used this IP address in the past to talk to a different server, and somebody at this IP address is talking to a new server, it's telling me that this could be a security risk. In our case, it's not. It needs to go into my known hosts file on line 210 and delete the line that's there. Delete that. Save. And then let's try that command again.

It's going to ask me to save the fingerprint to that server. And then it's going to ask us for the password. So for the image that we are using, you can SSH using a Vagrant and the password Vagrant and that lets us in.

So now that we know that's working, how do we tell Ansible to use that username and password for SSH? Well, not surprising, it's going to go back to variables. There is a variable called Ansible_user. Set that to Vagrant. And then to set another variable, it's space, then ansible_ssh_pass=vagrant.

This time we are trying the ping module. It works. Now, quick note about this SSH password. You know this is plain text which obviously for our VirtualBox is no big deal. In reality there is something called the Ansible vault, which is a way for you to store things like passwords securely without exposing them in plain text. We are going to talk about the vault down the line. But now that we have Ansible talking to our Vagrant machine, we need to create a playbook and start setting that machine up.

