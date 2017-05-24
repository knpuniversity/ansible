# Skipping Tasks based on Changed

Thanks to the `when` key, you can make your playbooks really really smart and really
really fast. What else could we do? Well, sometimes, our code doesn't change. When
that happens, the "Checkout Git repository" will report as "ok", so *not* Changed.

If we know that the code didn't change... then it might not make sense to install
our composer dependencies. After all, if the code didn't change, how would the
composer dependencies need to change? And we could skip other things, like running
the migration or even clearing the cache.

Under the "Git" task, register a new variable: `repo_code`:

[[[ code('b4eb384d36') ]]]

We already know from
the output that we're looking for the `changed` key on this variable. That means,
we could use `repo_code.changed` in the `when` option of some tasks to skip them.

## Using set_fact to Clean Variables

But, we can get fancier! Below this task, add a new one called "Register code_changed variable".
We'll use the `set_fact` module from earlier. This time, create a new variable called
`code_changed` set to, very simply, `repo_code.changed`:

[[[ code('bdf1003634') ]]]

The *only* reason we're doing this is to make our `when` statements a little cleaner.
Add our tag onto that.

Down below, under "Install Composer's Dependencies", add `when: code_changed`:

[[[ code('acd4c0ebbd') ]]]

Ah, so nice. Copy that and put it anywhere else it makes sense like "Execute migrations"
and "Clear Cache":

[[[ code('1b09a9e35f') ]]]

Phew! Ok, run the playbook - but take off the verbose flag:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy
```

As cool as this is, you need to be careful. As you make your playbook smarter, it's
also more and more possible that you introduce a bug: you skip a task when it should
actually run. I've just made a small mistake... which we'll discover soon.

But for now, it's so cool: the 3 Composer tasks were skipped, as well as installing
Composer's dependencies, migrations and the "Clear Cache". And thanks to that, the
playbook ran *way* faster than before.

It's time to talk about getting our playbook a bit more organized. As you can see...
it's getting big... and I'm getting a bit lost in it. Fortunately, we have a few
good ways to fix this.
