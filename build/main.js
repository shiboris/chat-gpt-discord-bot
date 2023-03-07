"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const http_1 = __importDefault(require("http"));
const dotenv_1 = __importDefault(require("dotenv"));
const chat_gpt_1 = __importDefault(require("./chat-gpt"));
dotenv_1.default.config();
http_1.default
    .createServer((req, res) => {
    res.write('online');
    res.end();
})
    .listen(3030);
const client = new chat_gpt_1.default({
    intents: ['Guilds', 'GuildMembers', 'GuildMessages', 'MessageContent'],
});
const discordToken = String(process.env.DISCORD_TOKEN);
client.login(discordToken);
