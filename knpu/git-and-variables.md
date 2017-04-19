# git & Variables

With PHP setup, it's time to actually pull down our code onto the server. Let's think
about what this process will look like. First, we need to create a directory. Then
we need to clone our code with `git`. Well there are several ways to get code onto
a machine - but using `git` is a really nice option.

## The file Module

Let's create that directory. Not surprisingly, Ansible has module for this. Search
for the `Ansible file module`. Yes! This helps set attributes on files, symlinks
and directories. If you need to create any of these or set permissions, this is
your friend.

The most important option is `path`, but there are few other we'll need, like `state`,
where you choose what type of "thing" the path should be, and also, `owner`, `group`
and `mode` for permissions goodness.

You know the drill: create a new task: `Create a project directory and set its permissions`.
Use `become: true`, use `file` and set `path` to, how about, `/var/www/project`.
The `state` should be `directory` and add `owner: vagrant` and `group: vagrant`.
This will let our SSH user write these files.

***TIP
In some setups, you might want to have your web-server user - e.g. `www-data` - be
the owner if this directory. Then, you can use `become: www-data` on future calls
to become that user.
***

Oh, and set `recurse: true` - in case `/var/www` doesn't exist, it'll create
that!

## Referencing Variables

But don't try this yet! Thanks to our `hosts.ini` setup, we've told Ansible that
we want to SSH as the user `vagrant`. We did that by overriding a built-in variable
called `ansible_user`. Well, guess what? We can *reference* that same variable in
our playbook! Instead of hardcoding `vagrant`, use Jinja: `{{ ansible_user }}`. Repeat
that next to `group`.

Start up your playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

## The git Module

While we're waiting, let's move to the next step: cloning our code via git. Unfortunately,
Ansible does *not* have a `git` module to help us... bah! Just kidding, it totally
does! Search for the `Ansible git module` to find it.

The module *does* have `git` as a requirement - but we already installed that. So
our job is pretty simple: pass it the `repo` we want to clone, and the `dest`-ination we
want to clone to.

Do it! Go to `http://github.com/symfony/symfony-standard`. To start, we'll pull down
the Symfony Standard Edition *instead* of our MooTube code. But that's just temporary.
I'll click the "Clone" button and copy the URL.

Now, add a task: `Checkout Git repository`. We do *not* need `become: true` because
we *own* the destination directory. Go straight to `git`, then `repo` set to the
URL we just copied. For `dest`, put `/var/www/project`. Add `force: yes`: that'll
discard uncommitted changes if there are any.

Head back to your terminal. Sweet! The directory *was* created! In the VM, the
`/var/www/project` directory is empty.

## Creating a Variable

Before we run the new `git` task, I want to solve *one* last thing: we have duplication!
The directory name - `/var/www/project` is in *two* places. Lame!

Well, good news: in addition to overriding variables - like `ansible_user` - we
can create *new* variables.

Go all the way to the top of the file - right below `hosts` - though the order doesn't
matter. Add a new `vars:` key, then below, set `symfony_root_dir: /var/www/project`.

Copy that new variable name and use it *just* like before: `{{ symfony_root_dir }}`.
Repeat that next to `dest`.

Ok, *now* I'm happy. Move over and try the playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Looks good! Check the directory in the VM:

```terminal
ls /var/www/project
```

Got it! The code is here, but it's not working yet: we need to install our Composer
dependencies!
