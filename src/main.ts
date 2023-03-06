import http from 'http';
import dotenv from 'dotenv';
import ChatGpt from './chat-gpt';

dotenv.config();

http
  .createServer((req, res) => {
    res.write('online');
    res.end();
  })
  .listen(3030);

const client = new ChatGpt({
  intents: ['Guilds', 'GuildMembers', 'GuildMessages', 'MessageContent'],
});

const discordToken = String(process.env.DISCORD_TOKEN);
client.login(discordToken);
