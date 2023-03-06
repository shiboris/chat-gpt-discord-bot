import { ClientOptions, Client, Message, TextChannel } from 'discord.js';
import { prompt } from 'ts-chatgpt';

export default class ChatGpt extends Client {
  public readonly prefix = 'ai ';

  constructor(options: ClientOptions) {
    super(options);

    this.once('ready', () => {
      console.log('Ready!');
    });

    this.on('messageCreate', async (message: Message) => {
      const messageContent = this.checkMessage(message);
      if (messageContent === false) return;

      const resultMessage = await this.callChatGptApi(messageContent);
      const channel = this.channels.cache.get(message.channelId) as TextChannel;

      channel.send(String(resultMessage));
    });
  }

  private checkMessage(message: Message) {
    if (!message.content.startsWith(this.prefix)) return false;
    if (message.author.bot) return false;

    return message.content.replace(this.prefix, '');
  }

  private async callChatGptApi(messageContent: string) {
    const response = await prompt({
      model: 'gpt-3.5-turbo-0301',
      messages: [
        {
          role: 'user',
          content: messageContent,
        },
      ],
    });

    return response?.choices?.[0].message.content;
  }
}
