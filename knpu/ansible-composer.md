# Installing Composer & the script Module

With our project cloned, the next step is obvious: use Composer to install our dependencies!
Actually, we used the Composer module earlier from the command line. Google again
for "Ansible Modules" and find the "All Modules" page. I'll find the `composer`
module and open that in a new tab.

This module is really easy... except for one problem. Under Requirements, it says
that `composer` already needs to be installed. We have *not* done that yet... and,
unfortunately, it can't be installed with `apt-get`.

## Installing Composer Programmatically?

So how *do* you install it? Check out http://getcomposer.org and click "Download".

Normally, we just paste these lines into our terminal and celebrate! But... there's
this problematic fine print at the bottom:

> Do not redistribute the install code. It will change for every version of the install.

Huh. Composer includes a bit of built-in security: a sha hash to make sure that
the installer hasn't been tampered with. If we tried to use these 4 commands in
Ansible, it would work... for awhile. But next time the installer is updated, and
that sha changed... it would *stop* working.

What to do? Check out that [how to install Composer programmatically](https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md)
link. Eureka: a shell script that will safely download the latest version of Composer.
The end result is a `composer.phar` file wherever we run this script from.

## Installing Composer with script

Our mission is clear: somehow, execute this shell script via Ansible. But before
we do that, near the top, add one new task: Install low-level utilities. Here, use
the `apt` module and the `with_items` syntax to install `zip` and `unzip`. Without
these, Composer will run *really* slowly and you'll blame Jordi when you should
be thanking him.

Now, back to our main job: how can we execute a script on a host? Why, with... the
`script` module of course!

> Runs a local script on a remote node after transferring it

Neato! We just point it at a local script, and it takes care of the rest. Go copy
the script and, in our `ansible` directory, create a new `scripts` directory and
a new file called `install_composer.sh`. Paste the code there.

Back in the playbook, at the bottom, create a new task: Download Composer. Use the
`script` module. Then, the easiest way to use this is to literally put the script
filename on the same line as the module name: `script: scripts/install_composer.sh`.
Actually, every module can be used with a one-line syntax like this... but since
line breaks are pretty cheap these days, I usually organize things a bit more.

Thanks to this task, we'll have a new `composer.phar` file in our home directory,
which is where this task - well, all tasks - are running. But that's not enough:
we need to move this to `/usr/local/bin/composer`.

## Moving Composer Globally

Create another task: Move Composer globally. This time, use `become: true` and use
the `command` module. In your browser, go find the `command` module. Like with `script`,
`command` has a short syntax. We'll say: `command: mv composer.phar` to `/usr/local/bin/composer`.

If you're a little surprised that I'm using the `command` module instead of some
built-in `file` or `move` module... me too! In general, you should always look for
a built-in module first: they're always more powerful than using `command`. But sometimes,
like with moving files, `command` *is* the right tool for the job.

Add *one* more task to make sure the file is executable: "Set Permissions on Composer"
with `become: true`.

Remember, Ansible is all about *state*. The job of the `file` module isn't really
to *create* files or symlinks. Instead, it's to make sure that they *exist* and
have the right permissions. In this case, we're going to take advantage of the
`mode` option to guarantee that the file is executable.

In the playbook, use the `file` module, set `path` to `/usr/local/bin/composer`
and `mode` to `"a+x"` to guarantee that all users have executable permission.

Oh, and make sure the file you created is `install_composer.sh`.

Time to give this a try. Find your main machine's terminal and run the playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Back on the VM, I'm in my home directory. And right now, it's empty. But if you're
really fast, you can see the installation script doing its work. There it is:
`composer-setup.phar`, `composer-temp.phar`, `composer.phar` and then it's gone
once our task moves it. Yes!

And finally, we can type `composer`. Let's install some dependencies already!
