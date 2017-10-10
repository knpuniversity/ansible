# Safe Migrations

When we deploy, our migrations run! Woohoo! Yep, we can just generate migrations
and everything happens automatically on deploy.

## Making a Schema Change

Oooooooh, but there's a catch! Open `src/AppBundle/Entity/Video.php`. This entity
has a field called `image`. Ya know what? I'd rather call that *poster*, because
it's the *poster* image for this video.

Because the annotation doesn't have a `name` option, renaming the property means
that the column will be renamed in the database. And that means... drum roll...
we need a migration!

But first, we also need to update a few parts of our code, like our fixtures. I'll
search for `image:` and replace it with `poster:`. Then, open a template:
`app/Resources/views/default/index.html.twig`. Search for `.image`. Ah, yes! Change
that to `.poster`.

Brilliant! All we need to do now is write a migration to rename that column. Easy!
Switch to your local terminal and run

```terminal
./bin/console doctrine:migrations:diff
```

Go check it out in `app/DoctrineMigrations`. Wow... it's actually perfect:

	ALTER TABLE video CHANGE image (to) poster...

Doctrine is smart enough to know that we should *rename* the column instead of
dropping the old column and adding a new one.

## Dangerous Deploy Ahead!

Great! Let's deploy! Right!? Sure... if you want to take your site down for a minute
or two! Can you see the problem? If we deploy now, this migration will run... and
about 1 minute later, the deploy will finish and the new code will be used. The
problem is *during* that period. As *soon* as this migration executes, the `image`
column will be *gone*... but the live site will still try to use it! That's a huge
problem.

Nope, we need to be smarter: we need to write *safe* migrations. Here's the idea:
only write migrations that *add* new things & never write migrations that *remove*
things... unless that thing is not being used at *all* by the live site.

## Writing Safe Migrations

This creates a *slightly* different workflow... with *two* deploys. For the first
deploy, change the migration: `ALTER TABLE video ADD poster`. We're not going to
remove the `image` column yet. But now, we *do* need to migrate the data:
`UPDATE video SET poster = image`.

Honestly, I usually don't worry about the `down()`... I've actually never rolled
back a deploy before. But, let's update it to be safe: `SET image = poster`, and
then `ALTER TABLE` to drop `poster`.

*This* is a safe migration. First, try it locally:

```terminal
./bin/console doctrine:migrations:migrate
```

Perfect! And now... deploy! Right? No! Stop that deploy! If you deploy now... well...
you're not going to deploy *anything*. We have not committed or pushed our changes
yet!

This is actually the first time that we've made changes to our *code*, and that's
why this is the first time we've needed to worry about this. Commit the changes
and run:

```terminal
git push origin master
```

*Now* deploy:

```terminal-silent
ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass
```

Type in `beefpass` and deploy to master. If you watch closely, the migration task
should show as *changed*... because it *is* running one migration.

The site still works with *no* downtime. 

## Removing Columns/Tables

What about the extra `image` column that's still in the database? Now that it's
not being used at *all* on production, it's safe to remove on a *second* deploy.
Run:

```terminal
./bin/console doctrine:migrations:diff
```

This time it perfectly sees the DROP. Commit this new file and push:

```terminal-silent
git push origin master
```

Deploy!

```terminal-silent
ansible-playbook ansible/deploy.yml -i ansible/hosts.ini --ask-vault-pass
```

This time, when the `image` column is removed, the production code is *already*
not using it.

## The Edge Case: Updated Data

There *is* still one edge-case problem. On the first deploy, we used an UPDATE
statement to set `poster = image`. That makes those columns identical. But, for
then next few seconds, the production code is *still* using the old `image` column.
That's fine... unless people are making *changes* to its data! Any changes made to
`image` during this period will be *lost* when the *new* production code stops reading
that column.

If you have this problem, you're going to need to be a little bit more intelligent,
and potentially run another UPDATE statement immediately after the new code becomes
live.

Ok! Our final migration ran, the deploy finished and the site still works... with no
downtime.

Next! Let's share files... and make our deploy faster!
