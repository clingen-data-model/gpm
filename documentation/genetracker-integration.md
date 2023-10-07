# TODO- this needs more clarification

Right now, the GPM directly accessess the mysql database of the genetracker.
This is not optimal from the standpoint of isolation/security, and also
makes dev setup/mocking a bit more complicated. Ideally the genetracker
would have an api to abstract this away...
