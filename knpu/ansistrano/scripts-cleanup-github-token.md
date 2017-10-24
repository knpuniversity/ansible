# Cleanup & GitHub OAuth Token

It's time to *polish* our deploy. Right now, you can surf to the `/app_dev.php` script
on production. You can't really *access* it... but that file should not be deployed.

Back on the Ansistrano docs, look at the workflow diagram. So far, we've been hooking
into "After Symlink Shared", because that's when the site is basically functional
but not yet live. To delete `app_dev.php`, let's hook into "Before Symlink". It's
basically the same, but this is the last opportunity to do something *right* before
the deploy becomes live.

Scroll down to the variables section and copy `ansistrano_before_symlink_tasks_file`.
In `deploy.yml`, paste that and set it to a new file: `before-symlink.yml`:

[[[ code('a88a3f4b02') ]]]

In the `deploy/` directory, create that! We only need one new task:
"Remove sensitive scripts from web/ dir". Use the `file` module:

[[[ code('2cbc011f8c') ]]]

For `path`, first go back to `deploy.yml`, create a new variable `release_web_path`
and set it to `{{ ansistrano_release_path.stdout }}/web`:

[[[ code('7a40142ad3') ]]]

Copy that variable and get back to work! Set `path` to `{{ release_web_path }}/{{ item }}`:

[[[ code('e8730f6aa5') ]]]

We're *also* going to delete this `config.php` script:

[[[ code('837d656530') ]]]

Set `state` to `absent` and add `with_items`. Delete 2: `app_dev.php` and `config.php`:

[[[ code('5768216709') ]]]

Oh, and since I never deployed my `services.yml` change, let's commit these changes,
push, and deploy to the cloud!

```terminal-silent
ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass
```

## Composer GitHub Access Token

While we're waiting, there is one thing that *could* break our deploy: GitHub
rate limiting. If `composer install` accesses the GitHub API too often, the great
and powerful GitHub monster will kill our deploy! This *shouldn't* happen, thanks
to Composer's caching, but it *is* possible.

***TIP
Actually, a change made to Composer in 2016 effectively fixed the rate limiting problem.
But the fix (GitHub OAuth token) we will show will allow you to install dependencies
from private repositories.
***

Google for "Composer GitHub token" to find a spot on their troubleshooting docs
called [API rate limit and OAuth tokens][api-rate-limit-and-oauth-tokens].
All we need to do is create a personal access token on GitHub and then run this
command on the server. This will please and pacify the GitHub monster, and the
rate limiting problem will be gone.

Click the [Create][github_tokens] link and then "Generate new token". Think of
a clever name and give it `repo` privileges.

## Setting the GitHub Token in Ansible

Perfect! We *could* run the `composer config` command manually on the server.
But instead, let's do it in our provision playbook: `ansible/playbook.yml`.

This is pretty easy... except that we *probably* don't want to hardcode my access
token. Instead, we'll use the Ansible vault: a *new* vault just for `playbook.yml`.
As soon as the deploy finishes, create it:

```terminal
ansible-vault create ansible/vars/provision_vault.yml
```

Use the normal `beefpass` as the password. And then, add just one variable:
`vault_github_oauth_token` set to the new access token:

```yaml
# ansible/vars/provision_vault.yml

vault_github_oauth_token: 146f9e4f876164866d5afd956843d9141c4c6c47
```

Save and close! Whenever I have a vault, I also like to create a simple variables
file. Create `provision_vars.yml`. And inside, set `github_oauth_token` to
`vault_github_oauth_token`:

[[[ code('82999275ed') ]]]

Finally, in `playbook.yml`, let's include these! Include `./vars/provision_vault.yml`
and then `./vars/provision_vars.yml`:

[[[ code('2624bebc3d') ]]]

We now have access to the `github_oauth_token` variable.

We have a few tasks that install the Composer executable:

[[[ code('5ac65ae25c') ]]]

After those, create a new one: "Set GitHub OAuth token for Composer". Use the `composer`
module and set `command` to `config`:

[[[ code('8cde7e81b4') ]]]

The docs show the full command we need. Copy the arguments and set `arguments`
to that string. Replace the `<oauthtoken>` part with `{{ github_oauth_token }}`:

[[[ code('27c97d1b3e') ]]]

Also set `working_dir` to `/home/{{ ansible_user }}`... the `composer` module requires
this to be set. And at the end, add a tag: `github_oauth`:

[[[ code('f8e45fbf60') ]]]

Why the tag? Because I *really* don't want to re-run my *entire* provision playbook
*just* for this task. Translation: I'm being lazy! Run the provision playbook, but
with an extra `-t github_oauth`, just this one time:

```terminal-silent
ansible-playbook ansible/playbook.yml -i ansible/hosts.ini --ask-vault-pass -l aws -t github_oauth
```

Use `beefpass`! Great! So... is this working?

On GitHub, you can see that the token has *never* been used. When we deploy,
`composer install` *should* now use it. But first, back on the server, run
`composer clear-cache`:

```terminal-silent
composer clear-cache
```

to make sure it actually makes some API requests and doesn't just load everything
from cache.

Now, deploy!

```terminal-silent
ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass
```

As soon as this executes the "Composer install" task, our access key should be used.
There it is... and yes! The key was used within the last week. Now we will never have
rate limiting issues.


[api-rate-limit-and-oauth-tokens]: https://getcomposer.org/doc/articles/troubleshooting.md#api-rate-limit-and-oauth-tokens
[github_tokens]: https://github.com/settings/tokens
