# Organizing with include

Our playbook is a mess: there's just a *lot* of tasks in one file. Let's get
organized!

There are a few "categories" of things in the playbook. One category deals with
"Bootstrapping Symfony", like installing the composer dependencies, fixing the
`var` directory permissions and all of our Symfony console commands:

[[[ code('02ae009e65') ]]]

## Using include()

I'd like to isolate these tasks into their own file. And... there are 2 good ways
to do that. The first is.... just to include it. Create a new directory inside
`ansible` called `includes/`, and inside there, a new file called `symfony-bootstrap.yml`.
Start with the `---` on top:

[[[ code('61647af9c7') ]]]

Now, let's move some tasks here! Grab "Install Composer Dependencies" and move it.
We also want "Fix var directory permissions" all the way down to the end: "Clear Cache".
Delete all of it and paste it into `symfony-bootstrap.yml`:

[[[ code('bdf91bc450') ]]]

Now that these are in their own file, they should *not* be indented: so un-indent
them twice.

In the playbook, to bring those in - it's really cool: where you would normally
put a module, say `include: ./includes/symfony-bootstrap.yml`:

[[[ code('8e621b401f') ]]]

The tasks *will* run in a *slightly* different order than they did before, but it
won't make any difference for us. But, to be sure try the playbook with `-t deploy`:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy
```

All good! Including a file is the easiest way to organize and re-use tasks. But,
there's a better, cooler, more robust, more hipster way. It's called roles.
