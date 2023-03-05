import { Message, Client } from 'discord.js';
import http from 'http';
import dotenv from 'dotenv';

http
  .createServer(function (req, res) {
    res.write('online');
    res.end();
  })
  .listen(3030);

dotenv.config();

const client = new Client({
  intents: ['Guilds', 'GuildMembers', 'GuildMessages', 'MessageContent'],
});

client.once('ready', () => {
  console.log('Ready!');
  console.log(client.user?.tag);
});

client.on('messageCreate', async (message: Message) => {
  console.log(message.content);
  if (message.author.bot) return;
  if (message.content.startsWith('!ping')) {
    message.reply('Pong!');
  }
});

client.login(process.env.TOKEN);
