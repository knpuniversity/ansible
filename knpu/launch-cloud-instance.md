# Launch a Cloud Instance!

Alas, this is our *final* chapter. So, I want to do something fun, and also talk
about how Ansible can be pushed further.

First, our Ansible setup could be a lot more powerful. We already learned that
instead of having one big play in our playbook, we could have *multiple* plays.
One play might setup the web servers, another play might provision the database
servers, and one final play could configure all of the Redis instances. We're
using one play because - in our smaller setup - all of that lives on the same host.

Also, each host group can have *multiple* servers below it. We could launch 10
EC2 instances and provision all of them at once.

And finally, Ansible can even be used to *launch* the instances themselves! A few
minutes ago, we manually launched the EC2 instance through the web interface.
Lame! Let's teach Ansible to do that.

## Launching EC2 Instances?

How? A module of course! The `ec2` module. This module is really good at interacting
with EC2 instances. Actually, if you click the [Cloud Modules][list_of_cloud_modules]
section on the left, you'll find a *ton* of modules for dealing with EC2 and many
other services, like `IAM`, `RDS` and `S3`. And of course, modules exist for all
of the major cloud providers. Ansible rocks!

So far, our playbook has been executing commands on the remote hosts - like our virtual
machine. But, in this case... we don't need to do that. Yea, we can run the `ec2`
module *locally*... because the purpose of this module is to talk to the AWS API.
In other words, it doesn't matter what host we execute it from!

Wherever you decide to execute these tasks, you need to make sure that something
called [Boto][boto] is installed. It's an extension for Python... which you might
also need to install locally. So far, Python has already come pre-installed on our
VM and EC2 instances.

If you're not sure if you have this Boto thing, just try it. If you get an error
about Boto, check into installing it. 

## Creating the Playbook

Since these new tasks will run against a new host - localhost - we can organize them
as a new *play* in our playbook... or create a new playbook file entirely. To keep
things simple, I'll create a new playbook file - `aws.yml`.

Inside, you know the drill: start with the host, set to `local`. Below that, set
`gather_facts` to `false`:

[[[ code('291a6cd552') ]]]

What's that? Each time we run the playbook, the first task is called "Setup".
That task gathers information about the host and creates some "facts"... which
is cool, because we can use those facts in our tasks.

But since we're simply running against our local machine, we're not going to need
these facts. This saves time.

## EC2 Auth: AWS Secret Key

For the EC2 module to work, we need an AWS access key and secret key. You can find
these inside of the IAM section of AWS under "Users". I already have mine prepared.
Let's use them!

But wait! We *probably* don't want to hardcode the secret key directly in our playbook.
Nope, let's use the vault!

```terminal
ansible-vault edit ansible/vars/vault.yml
```

Type in `beefpass`. Then, I'll paste in 2 new variables: `vault_aws_access_key`
and `vault_aws_secret_key`:

```yaml
# ansible/vars/vault.yml
---
# ...
vault_aws_access_key: "AKIAJAWKEZQ6S7LM3EKQ"
vault_aws_secret_key: "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"
```

Save and quit!

Just like before, open `vars.yml` and create two new variables: `aws_access_key`
set to `vault_aws_access_key` and `aws_secret_key` set to `vault_aws_secret_key`:

[[[ code('379c3faed9') ]]]

Finally, open up `playbook.yml` so we can steal the `vars_files` section. Paste
that into the new playbook:

[[[ code('7c10d4e5a7') ]]]

To use the keys, you have two options: pass them directly as options to the `ec2`
module, or set them as environment variables: `AWS_ACCESS_KEY` and `AWS_SECRET_KEY`.
In fact, if those environment variables are already setup on your system, you don't
need to do anything! The module will just pick them up!

Let's *set* the environment variables... because it's a bit more interesting. Just
like before, use the `environment` key. Then set `AWS_ACCESS_KEY` to `{{ aws_access_key }}`.
Repeat for `AWS_SECRET_KEY` set to `{{ aws_secret_key }}`:

[[[ code('35e205a1be') ]]]

Boom! We are ready to start *crushing* it with this module... or any of those AWS
modules.

## Using the ec2 Module

And actually, using the module is pretty simple! We're just going to give it a lot
of info about the image we want, the security group to use, the region and so on.

Add a new task called "Create an Instance". Use the `ec2` module and start filling
in those details:

[[[ code('03c907147b') ]]]

For `instance_type`, use `t2.micro` and set `image` to `ami-41d48e24`:

[[[ code('2f2c02833d') ]]]

That's the exact image we used when we launched the instance manually.

Next, set `wait` to `yes` - that's not important for us, but it tells Ansible to
wait until the machine gets into a "booted" state. If you're going to do more setup
afterwards, you'll need this:

[[[ code('83eaf139e6') ]]]

Then, `group: web_access_testing`, `count: 1`, `key_name: Ansible_AWS_tmp`,
`region: us-east-2` and `instance_tags` with `Name: MooTube instance`:

[[[ code('cbd4f45190') ]]]

Obviously, tweak whatever you need!

Just like any other module, we can register the output to a variable. I wonder
what that looks like in this case? Add `register: ec2` to find out:

[[[ code('80e07f97f8') ]]]

Then, debug it: `debug: var=ec2`:

[[[ code('c67995b388') ]]]

Give it a try!

```terminal
ansible-playbook ansible/aws.yml -i ansible/hosts.ini --ask-vault-pass
```

Cool, it skipped the setup task and went straight to work! If you get an error about
Boto - either it doesn't exist, or it can't find the region - you may need to install
or upgrade it. I *did* have to upgrade mine - I could use the `us-east-1` region,
but not `us-east-2`. Weird, right? Upgrading for me meant running:

```terminal
easy_install -U boto
```

And, done! Yes! It's green! And the variable is *awesome*: it gives us an instance
id and a lot of other great info, like the public IP address. If I refresh my EC2
console, and remove the search filter... yes! Two instances running.

I can feel the power!

## Boot and then Provision?

We now have 2 playbooks: one for booting the instances, and another for provisioning
them. If you wanted Ansible to boot the instances and then provision them, that's
totally possible! Ultimately, we could take this public IP address and add it as
a new host under the `aws` group:

[[[ code('308422600a') ]]]

Of course... with our current setup, the `hosts.ini` inventory file is static: each
time we launch a new instance, we would need to manually put its IP address here:

[[[ code('0ec62eba85') ]]]

But, there *are* ways to have a *dynamic* hosts file. Imagine a setup where Ansible
automatically looks at the servers booted in the cloud and uses *them* for your
inventory. That's beyond the scope of this tutorial, but if you need that, go for
it!

Woh, we're done! Thanks for sticking with me to cover this huge, but *super* powerful
tool! When you finally figure out how to get Ansible to do your laundry for you,
send me your playbook. Or better, create a re-usable role and share it with the
world.

All right guys, seeya next time.


[list_of_cloud_modules]: http://docs.ansible.com/ansible/list_of_cloud_modules.html
[boto]: https://github.com/boto/boto
