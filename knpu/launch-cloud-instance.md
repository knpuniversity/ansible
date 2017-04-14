# Launch Cloud Instance

So far I've used Ansible to set up a existing server, but actually Ansible can be a lot more powerful than that in at least two ways. First, we already know that in our playbook instead of having just one big play that sets up one server, we could actually have multiple plays. One play that sets up our web servers and another play that sets up our database servers and another play that sets up our Redis servers, if you want.

Each server host group can actually have multiple servers below it. Also, Ansible can actually be responsible for creating those servers in the first place. A second ago when we deployed to Amazon, we actually went into the EC2 console and manually launched an instance. I want to have Ansible do that for me.

The way we're going to do this is via the Ansible EC2 module, a module that's really good with interacting with EC2 instances. Actually, if you click the cloud module section on the left, you'll see that there are a lot of modules dealing with EC2 and other things like IAM, RDS, S3, all the different AWS services, and other things like Azure and Digital Ocean and so on.

One weird thing is that in this case ... So far when we've been executing Ansible, we've been having the commands that have been executed executed on our remote host, our virtual machine, for example, but in this case, we don't need that. We actually can have the commands run locally because the purpose of these commands is to talk to Amazon's API. It doesn't really matter where we run these commands.

We're going to run them locally, which means locally, we need to make sure we have Python installed and something called Boto, which is an extension for Python, which you might need to install based on your system. If you get an error about Boto when you try to run this, then you need to look into installing it.

Since what we're doing here is so different than what we've been doing in our playbook, for simplicity, I'm actually going to create a new playbook file. In the Ansible director, I'm going to create an aws.yml file. In a second, we'll talk about how this could, in a real application be integrated with the playbook.yml.

Inside here, we're going to start just as we always do with host, and this time set to local so it runs in our local machine. Below that, I'm going to say gather facts false. You may notice whenever we run our playbook, there's a module that runs before all the other ones called setup. Setup module's gathering information about the host machine and setting those as variables, which is cool because we can use those variables in our tasks to do different things. Since we're just running this against our local machine, we don't need to gather any facts, so we can set gather facts to false just to save time.

In order for the EC2 module to work, we're going to need to use our AWS access key and AWS secret key. These can actually be fetched inside of the IAM section of AWS's dashboard. Under your users, you can see your access credentials there. I already have mine prepared, so I'm just going to go straight to use them.

Obviously, we know that we don't want to hard code our credentials directly into our playbook, so instead, we're going to use our vault. Let's go over onto your local machine and we'll run ansible-vault edit ansible/vars/vault.yml. We'll type in our [inaudible 04:12] password. Then here, I'm going to paste in two new variables, vault_aws_access_key and vault_aws_secret_key. Then I'll save that.

At this point, you guys know the drill. Now that I've put those into the vault, we're going to put them also into vars with a different name. Here we'll say aws_access_key is equal to vault_aws_access_key, and I'll paste that and we'll create one for the secret key as well. To use that, just like in our main playbook, we're going to load in those two vars files. Perfect.

To use these with the EC2 module, you have two options. First, you can pass the access key and secret key directly as options to the EC2 module, or you can set up environment variables, like AWS access key and AWS secret key. In fact, if those environment variables are already set in your system, then you don't need to do anything because Ansible will just pick those up.

I'm actually going to use Ansible to set those environment variables, which means just like before, I'll use the environment key and we'll say AWS access key is going to be set to AWS access key variable. The same thing on the next line. AWS_secret key is set to our secret key. Now we're totally ready to start using our module.

These two module [inaudible 06:19] is pretty straightforward. We're just going to give it lots of information about the image that we want to boot, like, for example, the AMI image that we want to use, the security group that it's going to be in, the region that it's going to be in, and so forth.

In our playbook, we'll add the tasks and we'll add a task called create an instance. We use the EC2 module and we'll just start filling in those details. What I'm going to do is copy all the same details that we used before.

We'll use a t2 micro instance, we'll use the AMI that you can see here, and we'll use the same security group and key pair. For instance_type, we'll use t2.micro. The image, we'll use ami-41d48e24. We'll set wait to yes. That's not that important, but it tells Ansible to wait until the machine gets into a booted state. We'll set the security group to web access testing. We'll boot just one server. We'll set our key name to ansible_aws_tmp, same [inaudible 07:37] as before, and we'll set the region to us east-2. We'll also set an instance tag called name to move to instance. That'll be the name of the instance.

Just like all modules, we can register a variable at the bottom. We'll register a variable called EC2, which is going to be useful because that will have information about the IP address of the instance. To see what it looks like, we use debug. We'll say var=ec2. That's an even shorter version of the debug module that we've seen so far.

All right. Moment of truth. Let's flip over. We'll run our playbook just like before, ansible-playbook ansible/aws.yml-I ansible/host.ini--ask-vault-pass. It skipped the setup task. It went straight to creating an instance. If you get an error here about Bobo, either it doesn't exist or it can't find your region, you may need to either install Bobo or upgrade your Bobo extension. I was able to install on us east 1 fine, but when I went to install on us east 2, I actually needed to upgrade it.

Boom. Check this out. Green. We have an instance ID. We have lots of information about it, including its public IP address, which is amazing. If I flip over to my management console and refresh this page, and get rid of my search filter, you'll see that I have two instances, including one that we just created. That's awesome.

Ansible can be used for lots of things. If you wanted to connect this idea of creating instances with then provisioning them, that is something you can absolutely do. It takes a little bit more work, because ultimately, we want to be able to take this public IP address that was created here and add it as a new host under our AWS. With our current and traditional setup, the host.ini inventory file is static, it's hard covered, it's something we manage, but there are ways for you to have dynamic host file, which means that as you boot servers into the cloud, when you're on Ansible, it will automatically see what servers you have booted onto the cloud and use that as the source for your inventory.

All right guys. Hope you enjoyed this. See you next time.

