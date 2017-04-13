# apt Package Upgrades & Requirements

The `apt` module is the key to getting *so* much stuff installed. But, it can do
more than that. When we first boot our server from the Ubuntu image, what guarantees
that our packages aren't completely out of date? Nothing! In fact, I bet a lot of
packages *are* old and need upgrading.

Check out the `apt` module options. See `update_cache`? That's equivalent to running
`apt-get update`, which downloads the latest package lists from the Ubuntu repositories.
We definitely need that. Then after, to actually upgrade the packages, we can use
the `upgrade` option.

## update_cache: Updating the Repositories Cache

Head back to your playbook and add a new task to update the APT package manager
repositories cache. Add `become: true`, use the `apt` module, and set `update_cache`
to `yes`:

[[[ code('69375ccc9c') ]]]

Remember, `yes` and `true` mean the same thing.

## upgrade: Upgrading Packages

Cool! Copy that task to create the next one: upgrade the existing packages. Now,
set `upgrade` to `dist`:

[[[ code('f48d0ef4cd') ]]]

There are a few possible values for `upgrade` - some upgrade more aggressively
than others.

Find your terminal and run that playbook!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

The first time you do this... it might take awhile - like several minutes. So go
get some coffee and bother your co-workers. And it makes sense that it's slow:
our server probably *is* out of date, so this is doing a lot of work. Thanks to the
power of TV, we'll fast-forward.

Yes! The "Upgrade installed packages" task says "changed". It *did* upgrade some
stuff!

## Module Requirements: Installing Aptitude (if needed)

Head back to the docs: one of the other values for the `upgrade` option is safe,
which I kind of like because it's a bit more conservative than `dist`. When we use
`safe`, it uses `aptitude`, instead of `apt-get`. That's important, because not all
Ubuntu images come with `aptitude` installed out-of-the-box.

In fact, scroll up a bit. The `apt` module has a "Requirements" section. Interesting...
It says that the host - meaning the virtual machine in our case - needs `python-apt`,
which our VM has, and `aptitude` to be installed for things to work. So far, we think
of modules as standalone workers that take care of everything for us... and that's
mostly true. But sometimes, modules have *requirements*. And it's up to us to make
sure those requirements are met *before* using the module.

Open a new terminal tab and SSH into the VM with:

```terminal
vagrant ssh
```

Let's see if aptitude is installed:

```terminal
aptitude
```

It opens! So it *is* installed. Hit "q" to quit.

In this case, the requirement is already met out-of-the-box. But in other situations,
in fact, in older versions of this Ubuntu image, you may need to add a task to install
`aptitude` via the `apt` module.

But now, just set `upgrade` to `safe`:

[[[ code('d1fd3b6e59') ]]]

Then, try the playbook again to make sure it's still happy!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

It didn't make any changes... but it *is* still happy! We rock!

With repositories cache and packages upgraded, we can go crazy and install everything
we need.

## Installing git

So let's add a task to install the Git version control system: we'll use it to "deploy"
our code. Like always, `become: true`, use the `apt` module, and use `name: git`:

[[[ code('6102532c24') ]]]

I'll move this below `cowsay` - but the order between these doesn't matter.

Try this out:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

It *looks* like it worked. How could we know? Move over to the terminal tab that's
already SSH'ed into the VM. Run `git --version`.  Yes!

## Using state: latest

Back on the `apt` docs, there's an option called `state` with values `latest`,
`absent`, `present` or `build-dep`. This shouldn't be surprising: this module is
a lot smarter than simply running `apt-get install`: the module helps guarantee
that a package is in a specific *state*, like "present" or "absent"... if you wanted
to make sure that a package was *not* installed.

Add `state` to our task, set to `latest`:

[[[ code('fa9eaef070') ]]]

Now, instead of simply making sure that
the package is *present* on the system, it will make sure that it's upgraded to the
*latest* available version. This setting is a bit more aggressive - so do what's
best for you.

Try the playbook again!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini
```

Ok, it didn't make any changes: `git` is already at the latest version... which makes
sense... because we just installed it a minute ago. But in the future, when a new
version comes out, our playbook will grab it.

Ok, let's get PHP 7 installed!
