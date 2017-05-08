# Host Group Vars

Right now, both my EC2 machine *and* my virtual machine are configured to respond to
`mootube.l`. And that means I can only access one at a time: I can setup my `/etc/hosts`
to make `mootube.l` point to my EC2 instance *or* my VM... but not both.

What if instead, we setup the VM to be `mootube.l` and the EC2 instance to be
`mootube.ec2`? That would fix it! How can we do that?

The problem is that in our `roles/nginx/vars/main.yml` file, we have a `server_name`
variable... but it's just hardcoded to `mootube.l`. I want to override that variable
to a different value for each host group. And that is totally possible.

But first, re-open  `/etc/hosts` and point `mootube.l` back to the virtual machine
IP. Then, add a new `mootube.ec2` entry that points to the EC2 instance.

Nice! 

## Setting Variables for a Host Group

Now, how can we override the `server_name` variable *only* for the `aws` host group?
Create a new directory called `group_vars` with a file inside: `aws.yml`. *Just*
by having this exact directory and filename, whenever the `aws` group is executed,
it will automatically load this file and use the variables inside. But those variables
will *only* apply to the `aws` group.

Inside, create a new `host_server_name` variable set to `mootube.ec2`.

Copy that variable name. Next, open `roles/nginx/vars/main.yml`, replace the hardcoded
`mootube.l` with something fancier: `{{ host_server_name|default('mootube.l') }}`.

This says: use `host_server_name` if it exists. But if it doesn't, default to `mootube.l`.
This should give us a unique `host_name` variable for each group.

We're ready: try the playbook:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini --ask-vault-pass
```

Nice - we can see a few changes, but only to the EC2 server, as it changes the host
name. Ding!

Go back to your browser and refresh `mootube.l`. This is coming from the VM: I know
because it has data! Now try `http://mootube.ec2`. Boom! This comes from EC2. Super
fun.

We just used Ansible to provision an entirely new server on EC2. Could we even use
it to *launch* the server programmatically? Nope! I'm kidding - totally - let's do
it!
