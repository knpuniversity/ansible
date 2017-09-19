# Secret Config

No matter how you deploy, you get into a situation where you somehow need to control sensitive production configuration, like your database password or our Loggly Token, a secret token to our Loggly server. Depending on your application, there're many ways that you might do this in your application. For a Symfony 3 application, we manage a parameters.yml file.

In the Symfony 4 application, we read from environment variables. But one way or another we need to put our sensitive credentials onto the server. So in this case, we're going to put all of our sensitive things into parameters.yml file. In Symfony 4, you will need to put those sensitive things as environment variables. We always had the same problem. How do I put these secret things onto the server in an automated way?

Of course, there are a few answers to that. This is where things get complicated. So for the parameters.yml, one thing we could do is we could store the production parameters.yml up on an S3 bucket, a private S3 bucket. Now as part of our deploy process, we could use the S3 module to download that into our project.

Another way, the way we are going to do it, is we are actually going to start with our parameters.yml.disk file here, and we're going to print out variables inside of this file. We are going to store those variables securely inside the Ansible vault. Something we talked about in the Ansible tutorial. If you haven't seen it before, it's okay.

Here's how we start. First print the new Ansible vault by saying Ansible vault create Ansible/vars/deploy_vault.yml. We'll create a variables file that's specific to deploy. You need to give it a password. I'll give it beefpass.

And set up here, we're just going to start creating variables. Variables that we can then use inside of our parameters.yml.disk file. So if you look around, we have a few things that probably need to be dynamic. Maybe the secret, the Loggly Token, the database host, the database user and the database password. You might have more.

So inside this file, I'm going to create some variables. Vault_symphony_ secret set to 'udderly secret' string. Vault_loggly_token set to our production loggly token, which is this long string. Make sure you get your own Loggly token because that won't actually work.

Then we'll say vault_database_host: 127.0.0.1, vault_database_user: root, and vault_database_password, set to null.

Now, in my application when we provision the server, we actually install MySQL server locally. So I'm using the local database server and I haven't bothered to set up a proper root password.

In a real application, if I were developing deploying to AWS, I would use Amazon's RDS. Basically, they give you a database server so you don't need to manage on your own. In that case, your database host would be something specific to your RDS and you'd have a different user and a different password, but it's the same idea.

So when I'm finished, I'm going to write and quit that file. And what this does, it gives us a brand new file that we can't read but that Ansible can read. So instead of our deploy.yml, we can actually import this. Under vars_files we can say ./vars/deploy_vault.yml

Now because we have those new vault_ variables, we could go directly into parameters.yml and start using them. But, as a best practice, I also like to create a separate vars file, deploy_vars.yml, where I assign each of those vault variables to a normal variable.

So actually, let me go back and re-open my vaults. I'll type in "beefpass" as my password, I'm going to copy all those variables, and quit, and then paste them here. And my [inaudible 00:05:40] is, we're going to take each variable and create a new variable with it without the vault prefix. So I'll do this very quickly.

And you don't have to do this, the point is that you don't- since you can't easily read the vault, sometimes it's nice to be able to go look at file- plain text filing like this and say, "Oh! There's a variable called symfony_secret" and apparently there's a variable called vault_symfony_secret. That must be set in the vault. Then throughout the rest of Ansible, we'll just use these simpler variables here.

And this makes sure inside near the deploy.yml, you also bring in our deploy_vars.yml. So at this point we can go in our parameters.yml.dist and we can start to make this dynamic. So for database_host we'll print out database_host. Repeat that for database_user, database_password, and then down here for symfony_secret, and loggly_token.

So you guys get the idea. We put the secret things inside of the vault and then we can print them here inside of our parameters file.

Alright, so let's try it. Now if you run your playbook right now, with the syn command it's going to fail because it's can't decrypt that vault. So now we need to run our Ansible playbook with --ask-vault-pass. And path it- pass it: beefpass.

And you can also use a different flag where you point it at a file that contains the vault pass so you don't have to type it every time.

The pro of using the vault, is it's really easy. You can even commit those files, those encrypted files directly to your repository. The downside is, you now need to worry about this vault password.

If you store all of your secret things in S3 . . . then you can probably avoid using the vault. Alright so finish this perfectly, and you move out of out the current directory, and back in, open up our parameters.yml file- yes. You can see database_user root, udderly secret $string, and there's our loggly_token.

Alright, so we're one step closer. Still got a little bit [inaudible 00:09:13] but we're very very close to finishing our deploy.

