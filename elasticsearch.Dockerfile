FROM elasticsearch:7.17.22

RUN /usr/share/elasticsearch/bin/elasticsearch-plugin install analysis-icu analysis-phonetic

RUN echo "search.max_buckets: 100000" >> /usr/share/elasticsearch/config/elasticsearch.yml