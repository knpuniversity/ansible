# Install Stuff: The apt Module

Now we are dangerous! With the playbook setup, we can add more and more tasks that
use more and more modules. One of the most useful modules is called `apt` - our best
friend for installing things via `apt-get` on Debian or Ubuntu.

We're going to install a *really* important utility called `cowsay`. I already have
it installed locally, so let's try it:

```terminal
cowsay "I <3 Ansible"
```

OMG!

***TIP
If you have `cowsay` installed locally, make sure to run `export ANSIBLE_NOCOWS=1`.
Otherwise, Ansible will use `cowsay` for its output, which is hilarious, but a bit
distracting.
***

Since this is absolutely necessary on any server that runs MooTube, let's add a second
task to install it. Usually, I give my tasks a bit more structure, with a name that
mentions how important this is. Below, add the module you want to use: `apt`:

[[[ code('ce848f651a') ]]]

If you check out the `apt` module docs, you'll see that *it* has an option called
`name`, which is the package that we want to install. To pass this option to the
module, indent on the next line, and add `name: cowsay`:

[[[ code('ccd0278072') ]]]

Done!

Run the playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

## sudo: Using become

The ping works, but the install fails! Check out the error:

> Could not open lock file. Unable to lock the administration directory, are you root?

Of course! Ansible doesn't automatically run things with `sudo`. When a task *does*
need sudo, it needs another option: `become: true`:

[[[ code('3a71fa943d') ]]]

This means that we want to *become* the super user. In our VM, the `vagrant` user
can `sudo` without typing their password. But if that's not your situation, you *can*
configure the password.

Try it again!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

This time... it works! And notice, it says "changed" because it *did* install `cowsay`.
Now try it again:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Ah, hah! The second time it just says "Ok" with `changed=0`. Because remember: the
module doesn't just dumbly run `apt-get`! Its real job is to guarantee that `cowsay`
is in an installed "state".

Oh, and if you're Googling about Ansible, you might see `become: yes`. In Ansible,
whenever you need a Boolean value like `true` or `false`, Ansible allows you to say
"yes" or "no". Don't get surprised by that: "yes" means `true` and "no" means `false`.
Use whichever you like!

Time to get our system setup for real, with PHP, Nginx and other goodies!
