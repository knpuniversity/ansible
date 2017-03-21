# Modules

We need to talk about 2 important words in Ansible: modules and hosts.

Ansible comes built with a *ton* of things called *modules*: small programs that
do some work on the server. Most of the time, instead of saying:

> Ansible! Execute this command!

you'll say:

> Ansible! Execute this module and allow it to run whatever commands it needs to
> get its job done.

For example, if you want to install something on an Ubuntu server, instead of running
`sudo apt-get install php7.1`, you'll use the `apt` module. Need to edit a file?
There's a module for that too.

And when you execute a module, you'll of course always execute that module on one
or more servers. These are called hosts. So, in Ansible language, we'll say:

> I want to run this module on these hosts.

## Running your First Module

Ok, terminology garbage done. Let's do something! The simplest way to execute a module
is from the command line. `ansible localhost` means that - for now - we're going to
run this module against our local machine. Then, `-m command` to run the "command"
module - the simplest module in Ansible that allows you to... well... just run a
command directly on the server. Then, add `-a "bin/echo 'Hello Ansible"'` to pass
that as an argument to the `command` module:

```terminal
ansible localhost -m command -a "bin/echo 'Hello Ansible'"
```

Try it! We see some output and... Hello Ansible! Congrats! You just run your first
module: `command`. In this case, we can even remove the `-m` option - the `command`
module is so fundamental, it's the *default* module... if we don't pass one.

Hey! Let's try another module!

```terminal
ansible localhost -m ping
```

You can probably guess what this does: `ping` is a small program that makes sure
that we can contact the server.

What other modules can we use? Flip back to your browser and Google for "Ansible modules".
They have a few pages, like "modules by category". But let's
go to the full [All Modules](http://docs.ansible.com/ansible/list_of_all_modules.html)
page.

Woh! If we're commanding a robot army, we just found out that a lot of our robots
already know how to do a *ton* of stuff. Yes! This is all free functionality!

## The composer Module

One of these modules should look pretty interesting to PHP developers: the [composer](http://docs.ansible.com/ansible/composer_module.html)
module. Instead of executing Composer commands manually on the server, you can use
this.

For example, back on the command line, if you setup your project like I did, then
your `vendor/` directory is populated with files. Let's kill them! Be reckless by
running:

```terminal
rm -rf vendor
```

Now, take the `composer` module for a test drive:

```terminal
ansible localhost -m composer
```

Woh! It fails!

> Missing required arguments: working_dir.

The Ansible module documentation is pretty awesome: each page lists all of that
module's options, their default value and whether or not they're *required*, like
`working_dir`. And below, they usually have a bunch of really nice examples.

In this case, to pass the option, add `-a "working_dir=./"` to point to *this*
directory, since the module will run on *this* machine. We also need to pass
`no_dev=false`. That's just another option for this module - and we'll talk about
what it does in a little while.

Ok, try that!

```terminal
ansible localhost -m composer -a "working_dir=./ no_dev=false"
```

Woohoo! It looks like it's working! My terminal tab even shows the crazy things
it's doing behind the scenes.

***TIP
If it doesn't work on your machine, no big deal. This module requires you to have
PHP and composer installed. Soon, we'll *guarantee* that these are installed on
a fresh Ubuntu machine.
***

Once it's done... boom! We see the full output of everything that happened.

## The "changed" Status

AND, we see one *really* important thing: it says "changed true" and the output
is yellow. The module detected that this command made a *change* to the server.
Run the module again:

```terminal
ansible localhost -m composer -a "working_dir=./ no_dev=false"
```

Woh! Now the output is green and it says "changed false".

That is one of the most important superpowers of modules: not only do they make
something happen on your server, they're able to detect whether or not the server
*changed* by running the module. This will be important: later, we can trigger
different actions based on whether or not a module did or did not actually make
any changes.

But how the heck did Ansible know that the server didn't change the second time?
That cleverness is actually built into each module. The `composer` module is smart
enough to know that nothing changed based on the *output* of the command - the fact
that it said:

> Nothing to install or update

By the way, now that we're rocking Ansible, we could have cleared the vendor directory
via the `command` module:

```terminal
ansible localhost -a "rm -rf vendor"
```

To prove that worked, run the `composer` module again:

```terminal
ansible localhost -m composer -a "working_dir=./ no_dev=false"
```

Ha! Back to "changed true" with yellow output.

Next, let's talk about how we organize which servers we're running Ansible against.
Because obviously, we don't want to run against localhost forever!
