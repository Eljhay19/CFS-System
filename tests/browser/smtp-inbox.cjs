const net = require('node:net');

async function smtpInbox() {
    const messages = [];
    const connections = new Set();
    const server = net.createServer(socket => {
        connections.add(socket);
        socket.on('close', () => connections.delete(socket));
        socket.setEncoding('utf8');
        socket.write('220 cfs.test ESMTP test inbox\r\n');
        let buffer = '', data = false, body = [], recipients = [];
        socket.on('data', chunk => {
            buffer += chunk;
            while (buffer.includes('\n')) {
                const end = buffer.indexOf('\n');
                const line = buffer.slice(0, end).replace(/\r$/, '');
                buffer = buffer.slice(end + 1);
                if (data) {
                    if (line === '.') {
                        messages.push({ recipients: [...recipients], raw: body.join('\r\n') });
                        data = false; body = []; recipients = [];
                        socket.write('250 Message accepted\r\n');
                    } else body.push(line.replace(/^\.\./, '.'));
                } else if (/^(EHLO|HELO)/i.test(line)) socket.write('250-cfs.test\r\n250 8BITMIME\r\n');
                else if (/^MAIL FROM:/i.test(line)) { recipients = []; socket.write('250 OK\r\n'); }
                else if (/^RCPT TO:/i.test(line)) { recipients.push(line.slice(8).trim().replace(/^<|>$/g, '')); socket.write('250 OK\r\n'); }
                else if (/^DATA$/i.test(line)) { data = true; socket.write('354 End with a dot\r\n'); }
                else if (/^QUIT$/i.test(line)) socket.end('221 Bye\r\n');
                else socket.write('250 OK\r\n');
            }
        });
        socket.on('error', () => {});
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    return {
        messages, port: server.address().port,
        close: async () => { connections.forEach(socket => socket.destroy()); await new Promise(resolve => server.close(resolve)); },
    };
}

module.exports = { smtpInbox };
