#!/bin/sh
cd /app

# Start SSH agent
eval "$(ssh-agent -s)"
echo "$SSH_AUTH_SOCK"
# Add all SSH keys in /home/.ssh directory
for key in ~/.ssh/*; do
    ssh-add "$key"
done

# install backend dependencies
echo "Installing backend dependencies"
composer install
#echo "Dumping environment variables to .env.local.php"
#composer dump-env dev

# clear cache
echo "Clearing cache..."
rm -rf /root/.symfony5
php bin/console cache:clear

# wait for Elasticsearch to be up
until curl -s "elasticsearch:9200" > /dev/null; do
    echo "Waiting for Elasticsearch to be up..."
    sleep 5
done

# check elasticesearch indices
curl -f -s -I "elasticsearch:9200/${ELASTICSEARCH_INDEX_PREFIX}_text" > /dev/null
if [ $? -eq 0 ]; then
    echo "Text index already exists"
else
    echo "Creating text index (100 records max) ..."
    php bin/console app:elasticsearch:index charter 100
fi

# start the symfony server
echo "Starting server..."
symfony server:stop
symfony local:server:start --port=8000 --no-tls --allow-all-ip