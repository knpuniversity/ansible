# Safe Migrations

We just made running migrations part of our deploy script, which is awesome because as we make new migrations, they'll automatically be run when we deploy. But there's a catch.

Open up src, AppBundle, Entity, video.php. Inside here there is a field name called image. Let's imagine, for some reason, we decide we want to rename this to poster. When I do that, because I don't have the name of the column listed here, this will actually rename the column from image to poster, which means we're going to need a migration.

Of course, we also need to update a few other parts of our code, like our fixtures. I'll search for image colon and replace that with poster colon. Then in one template, app, Resources, views, default, index.html.twig, I'll search for .image, change that to .poster. Perfect.

So our code is already updated to use that new property, but we do need to change the column in the database. Easy. Let's flip over and, in our local terminal, we'll run /bin/console doctrine:migrations:diff. Let's go check this out. In app, DoctrineMigrations, it's actually perfect. Alter table video change image to poster. So it's smart enough to know that we should rename the column instead of dropping the old column and adding the new column.

So we should deploy, right? Well, not unless you want to take your site down for a minute or two. Because if we deploy now, during the deploy, once the migration's run, the image column will be gone, but our deployed code will still be trying to use that image column until our deploy finishes. So from the time that migration's run to the time that our deploy finally finishes, our database and our code will be out of sync. That is a problem.

So instead we need to do safe migrations. The idea of a safe migration is that you only do migrations that add things, like add new columns. You never do migrations that remove columns, unless that column is no longer being used at all, meaning the current site is not using that column.

Let me show you. It's a simple thing. It just means we do two separate deploys. This first deploy, we're going to do, "alter table video add poster," to just add the column. We're not going to remove the image column. Then we need to migrate the data now, so we'll say "update video set poster = image".

You can also change the down migration if you want. I never do the down migrations, because I never run down migrations. For the down, if you want to do the down, it's going to be almost the same thing in reverse. Set image = poster, then we'll do an alter table to drop poster.

Perfect. So that is a safe migration. As always, let's run it. Let's run it locally first. So /bin/console doctrine:migrations:migrate. Perfect. And now we'll deploy. Right? No, actually! Stop that deploy, because if you deploy now, you're not going to deploy anything, because remember, we haven't committed any of these changes. So the changes to the app and the src directory, they will not be deployed. We need to commit and push those changes before we deploy.

This is the first time that we've made changes to our actual code, so that's why this is the first time we've had to worry about this. So we'll commit renaming a column, then I'll do, "git push origin master", and now we will deploy. Use our beefpass password, deploy to master, and let's watch this happen.

The only interesting thing is that when you see the migrations task, you should see it as changed, because remember we set up our code so that the changed status shows correctly based on whether or not we had any migrations to run, which is just kind of a nice thing.



