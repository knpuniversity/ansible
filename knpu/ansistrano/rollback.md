# When things go wrong: Rollback

Yea... things go wrong. But, but, but! Ansistrano is *cool* because *if* something
fails, the symlink will never change and the site will *continue* using the old
code. If you're following our "Safe Migrations" philosophy, then deploying is even
*safer*: if your migrations run, but the deploy never finishes, your non-destructive
migrations won't hurt the current code.

But sometimes... a deploy *will* finish... only for you to have the sudden, horrible
realization, that something is now *massively* wrong with the site. Like, there
are zombies all over it or something.

At this moment, fate presents you with 3 options:

1. Run for your life!
2. Quickly make a new commit to fix things and re-deploy.
3. Rollback to the previous deploy.

Other than running out of the building screaming, rolling back is the *fastest*
way to escape the problem. And fortunately, Ansistrano has a *second* role all
about... rolling back: [ansistrano-rollback](https://github.com/ansistrano/rollback).

To install it, open the `requirements.yml` file and add an entry. For the version...
let's see. The latest version right now is `2.0.1`. Let's use that.

To install the role, on your local terminal, run:

```terminal
ansible-galaxy install -r ansible/requirements.yml
```

## Creating the Rollback Playbook

The rollback process will be its own, simple playbook. Create it: `rollback.yml`.
I'll open `deploy.yml` so we can steal things... starting with the host. Then, of
course, we need to include the new role: `carlosbuenosvinos.ansistrano-rollback`.

Rolling back is *way* simpler than deploying, but it works in the same way: there
are a few *stages* and we override variables to control things. The *only* variable
we *need* to override is `ansistrano_deploy_to`. In `deploy.yml`, we imported a
`vars_files` called `vars.yml`, and used it to help set this.

Let's do basically the same thing here. Copy part of the `vars_files` section, paste
it, and just import `vars.yml`: we don't need the vault. Back in `deploy.yml`,
also steal `ansistrano_deploy_to` and add that to `rollback.yml`.

## Rollback!

And... yea... that's basically it! So... let's try it! On the server, I'm already
in `/var/www/project`. My `current` symlink is set, and `releases` has 3 directories
inside.

Back on your local terminal... rollback!

```terminal
ansible-playbook ansible/rollback.yml -i ansible/hosts.ini
```

That's it. It should only take a few seconds. It *does* delete the old release
directory, but this - like most things - can be controlled with a variable.

Done! Back on the server... yes! The symlink *changed*. And one of our releases
is gone!

## Running Down Database Migrations

So rolling back is pretty easy. The most *common* issue involves migrations. Again,
if you follow our "safe migrations" philosophy, you have nothing to worry about.
But, if you're a bit more reckless - hey, no judgment - then you may need to manually
run the *down* direction on some of your migrations after a rollback.

Let's add a little "opportunity" for us to do that. Let me show you: copy the
`ansistrano_before_symlink_tasks_file` variable. In `rollback.yml`, paste this
and set it to a new `rollback/before-symlink.yml`.

Now, create a new `rollback/` directory with that file inside. Here, we'll add
just one task: "Pause to run migrations manually down". Use the `pause` module
to *freeze* the playbook and put up a message. This is *our* opportunity to manually
execute any *down* migrations we want.

Hey, I know: it's not automated and it's not awesome. But, things have gone wrong,
so it's time for us to take over.

Let's rollback *one* more time:

```terminal-silent
ansible-playbook ansible/rollback.yml -i ansible/hosts.ini
```

Here's the pause: it shows us the directory we should go into. Hit enter and it
keeps going. Oh, and cool! It *failed* on one of the servers! That was unplanned...
but awesome! That's the new server, and apparently we've only deployed there *two*
times. So, there was *no* old version to rollback to. Ansistrano smartly prevented
the rollback... instead of rollingback to nothing.

Ok guys... we're done! Woh! This tutorial was a *crazy* ride - I *loved* it! And
I hope you did too. You can now deploy a *killer* Symfony site - or *any* site with
Ansistrano. If this was all interesting but felt like a lot of work, don't forget
about the platform-as-a-service options like Heroku or platform.sh. You don't have
*quite* as much flexibility, and they're sometimes a bit more expensive, but a lot
of what we learned is handled for you and you can get started *really* quickly.

Whatever you choose, go forth and build something awesome! Ok guys, I'll seeya next
time!
