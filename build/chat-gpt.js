"use strict";
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
Object.defineProperty(exports, "__esModule", { value: true });
const discord_js_1 = require("discord.js");
const ts_chatgpt_1 = require("ts-chatgpt");
class ChatGpt extends discord_js_1.Client {
    constructor(options) {
        super(options);
        this.prefix = 'ai ';
        this.once('ready', () => {
            console.log('Ready!');
        });
        this.on('messageCreate', (message) => __awaiter(this, void 0, void 0, function* () {
            const messageContent = this.checkMessage(message);
            if (messageContent === false)
                return;
            const resultMessage = yield this.callChatGptApi(messageContent);
            const channel = this.channels.cache.get(message.channelId);
            channel.send(String(resultMessage));
        }));
    }
    checkMessage(message) {
        if (!message.content.startsWith(this.prefix))
            return false;
        if (message.author.bot)
            return false;
        return message.content.replace(this.prefix, '');
    }
    callChatGptApi(messageContent) {
        var _a;
        return __awaiter(this, void 0, void 0, function* () {
            const response = yield (0, ts_chatgpt_1.prompt)({
                model: 'gpt-3.5-turbo-0301',
                messages: [
                    {
                        role: 'user',
                        content: messageContent,
                    },
                ],
            });
            return (_a = response === null || response === void 0 ? void 0 : response.choices) === null || _a === void 0 ? void 0 : _a[0].message.content;
        });
    }
}
exports.default = ChatGpt;
