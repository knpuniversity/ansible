# parameters.yml: Handling Secret Config

No matter how you deploy, eventually, you hit the same problem: handling sensitive
configuration, like your production database password or Loggly token. Depending
on your app, this info might need to be stored in different places, like `parameters.yml`
for a Symfony 3 app or as environment variables for Symfony 4.

But no matter *where* the config needs to be stored, the problem is more or less
the same: how can we put secret things onto the server in an automated way?

## Options for parameters.yml

Like with *everything*, there are a few good answers. And this is where things
can get complicated. For `parameters.yml`, one option is to store the production
`parameters.yml` in a private S3 bucket. Then, during deployment, use the `s3`
Ansible module to download that into our project.

Another option - the way that *we* will do it - is to store a `parameters.yml.dist`
file in our project, and print out Ansible variables inside. To keep things secure,
those variables will be stored securely in the Ansible vault.

## Setting up the Deploy Vault

Let's create a new vault to store the secret values:

```terminal
ansible-vault create ansible/vars/deploy_vault.yml
```

Choose a safe password... something safer than what I'm choosing: `beefpass`.
Here's the plan: we're going to create some variables here, then use them inside
`parameters.yml.dist`. So, what needs to be dynamic? For now the `secret`,
`loggly_token`, `database_host`, `database_user` and `database_pass`.

Back in the vault, create some variables: `vault_symfony_secret` set to
`udderly secret $tring` and `vault_loggly_token` set to our production loggly
token... this long string. Get your own token... because this one won't work.

Then, `vault_database_host: 127.0.0.1`, `vault_database_user: root`, and
`vault_database_password` set to `null`.

In the provision playbook, we actually installed a MySQL server locally. That's
why I'm using the local database server... and I haven't bothered to create a proper
user with a decent password. You *should* care.

But also, if I were using AWS for a real application, I would use Amazon's RDS:
basically, a hosted MySQL or PostgreSQL database.. so that you don't need to manage
it on your own. In that case, the database host would be something specific to
your RDS instance. But, it's the same idea.

Save this file and quite. We now have a new, but encrpyted file with those variables.
Inside `deploy.yml`, under `vars_files`, add `./vars/deploy_vault.yml`.

## Creating a Simpler Variables File

At this point, we *could* go directly into `parameters.yml.dist` and start using
those `vault_` variables. But, as a best practice, I like to create a separate vars
file - `deploy_vars.yml` - where I assign each of those `vault_` variables to a normal
variable. Just, stay with me.

Re-open the vault file - type `beefpass` - and copy everything. Then, in
`deploy_vars.yml`, paste that. Now, for each variable, create a *new* variable
that's set to it, but *without* the `vault_` prefix. This is totally optional. The
advantage is that you can quickly see a list of *all* available variables, without
opening the vault. I can just look in here and say:

> Oh! Apparently there is a variable called `symfony_secret` that I can use!

Back in `deploy.yml`, make sure you import this new file: `./vars/deploy_vars.yml`.

## Variables inside parameters.yml.dist

Finally, in `parameters.yml.dist`, let's print some variables! For `database_host`,
print `{{ database_host }}`. Repeat that for `database_user`, `database_password`,
and then down below for `symfony_secret`, and `loggly_token`.

That's it! We put secret things inside of the vault and then print them here inside
of our parameters file.

Let's try it. Run the playbook with the same command:

```terminal-silent
ansible-playbook -i ansible/hosts.ini ansible/deploy.yml
```

Yep! This fails because it can't decrypt the vault file. Going forward, we need
to add a `--ask-vault-pass` flag. And then type, `beefpass`.

If this gets really annoying, you can store the password in a file and use
`vault-password-file` to point to that file.

And... done! Let's go check it out! Move out of the `current` directory and then
back in. Deep breath: open `parameters.yml`. Yes! Everything has its dynamic vault
value!

Ok, we're getting *really* close now! Next, let's run Composer and fix up some
permissions!
