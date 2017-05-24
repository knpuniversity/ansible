# The Variable Vault

The `symfony_secret` variable needs to be secret! I don't want to commit things
like this to my repository in plain text!

## Creating the Vault

One really cool solution to this is the *vault*: an *encrypted* variables file.

To create a vault file, go back to your main machine's terminal and run

```terminal
ansible-vault create ansible/vars/vault.yml
```

It'll ask you to create a vault password. We'll use - of course - `beefpass`. Keep
this handy: it's needed to *decrypt* the vault.

Nice! It opens up an editor. Once you're inside, treat this like a normal variable
file: add `---`, then a new variable: `vault_symfony_secret`:

```yaml
---
vault_symfony_secret: 
```

I am purposely starting the variable name with `vault_` - we'll talk about why
in a minute.

Then, set the value to `udderly secret $tring`:

```yaml
---
vault_symfony_secret: "udderly secret $tring"
```

Save with `:wq` Enter.

As *soon* as we do that, we have a `vars/vault.yml` file... and it is *not* human-readable:
it's encrypted:

[[[ code('3afd1a767c') ]]]

You can use the vault again to view it:

```terminal
ansible-vault view ansible/vars/vault.yml
```

It, of course, needs your password: `beefpass`. Or, you can edit it:

```terminal
ansible-vault edit ansible/vars/vault.yml
```

## Importing the vault File

In `vars.yml`, we can now remove the secret string and use the variable instead:
`{{ vault_symfony_secret }}`:

[[[ code('099f1229bc') ]]]

To make that variable available, import it like a normal variable file: `vars/vault.yml`:

[[[ code('2853707a48') ]]]

Make sure the vault is loaded first so we can use its variables inside `vars.yml`.

Let's walk through this: import `vault.yml`, then in `vars.yml` use the `vault_symfony_secret`
variable to set `symfony_secret`. Then, *that* variable is used elsewhere.

Why the big dance? Why not just call the variable `symfony_secret` in the vault?
Well, the `vault_` prefix is nice because it's really easy to know that it came from
the *vault*. That makes it easier to track things down.

## Running a Vaulted Playbook

Let's try it! Run the playbook like before, with `-t deploy` and a new flag:
`--ask-vault-pass`:

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t tags --ask-vault-pass
```

Nice! Enter `beefpass` for the password, use the `prod` environment, then let it
run! Nice! The "Set Symfony secret in parameters.yml" task reported Changed!

In the VM, let's check out that file:

```terminal
cat app/config/parameters.yml
```

```yaml
# This file is auto-generated during the composer install
parameters:
    # ...
    secret: udderly secret $tring
    # ...
```

Woohoo! There's our ridiculous secret string.

## Using a Secret Loggly Token

Let's do one more fun example. In your browser, remove the `app_dev.php` from the
URL. It still works!

Now, open `app/config/parameters.yml`. The last key is called `loggly_token`:

```yaml
# This file is auto-generated during the composer install
parameters:
    # ...
    loggly_token: 12345
    # ...
```

This is used in `config_prod.yml`:

[[[ code('c5f31091b0') ]]]

Basically, in the `prod` environment, the system is already setup to send *all* logs
to Loggly: a cloud log collector. Right now... there's not too much in my account.

The only thing *we* need to do to get this to work is replace this line in `parameters.yml`
with a working Loggly token. That's perfect for the vault!

## Adding more to the Vault

Edit the vault!

```terminal
ansible-vault edit ansible/vars/vault.yml
```

Add a new variable - `vault_loggly_token`:

```yaml
---
# ...
vault_loggly_token:
```

I'll paste a real token for my account and save:

```yaml
---
# ...
vault_loggly_token: fb4aa5b2-30a3-4bd8-8902-1aba5a683d62
```

And as much fun as it would be for me to to see all of *your* logs, you'll need
to create your own token - I'll revoke this one after recording.

We know the next step: open `vars.yml` and set `loggly_token` to `vault_loggly_token`:

[[[ code('3d26ee3d23') ]]]

And finally, we can use `loggly_token` in `symfony-bootstrap.yml`. Copy the previous
`lineinfile` task, paste it, and rename it to "Set Loggly token in parameters.yml":

[[[ code('e0e4f9a048') ]]]

Update the keys to `loggly_token`, and the variable to `loggly_token`:

[[[ code('f702ce3e0b') ]]]

Ok, try it!

```terminal
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini -t deploy --ask-vault-pass
```

Fill in `beefpass` and use the `prod` environment again.

Ding! Head to your VM and look at the `parameters.yml` file:

```terminal
cat app/config/parameters.yml
```

Nice! So... it should work, right? Refresh Mootube to fill in some logs. Now, reload
the Loggly dashboard. Hmm... nothing! What's going on?

Actually, we have another mistake in our playbook. Once again, I got too smart! We
*only* clear the cache when the code has changed:

[[[ code('36e3a68233') ]]]

But in this situation, `parameters.yml` changed... which is not technically part of
our code. In other words, this isn't working because we have not *yet* cleared our
prod cache.

I'll comment out the `when` under "Cache Clear" to make it *always* clear. Then,
just for right now, in the VM, clear the cache manually:

```terminal
./bin/console cache:clear --env=prod
```

If you get an error about permissions, that's fine: it's just having problems clearing
out the old cache directory. You can delete that:

```terminal
sudo rm -rf var/cache/pro~
```

Let's see if that was the problem! Refresh MooTube. Then, try the Loggly dashboard.
Got it! So cool! If you're coding with me, it might take a few minutes to show up,
so be patient!

Our playbook is really powerful. Could we use it to deploy to a cloud server like
AWS? Why not!? Let's do it!
