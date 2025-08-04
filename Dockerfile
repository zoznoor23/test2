FROM python:3.11-slim

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    chromium \
    chromium-driver \
    curl \
    unzip \
    xvfb \
    fonts-liberation \
    libnss3 \
    libxss1 \
    libasound2 \
    libatk-bridge2.0-0 \
    libgtk-3-0 \
    && rm -rf /var/lib/apt/lists/*

# Set environment variables for Chromium
ENV CHROME_VERSION=137.0.7151.119
ENV CHROME_BIN=/usr/bin/chromium
ENV CHROMEDRIVER_BIN=/usr/bin/chromedriver
ENV PATH=$PATH:/usr/local/bin
ENV DISPLAY=:99

WORKDIR /app

# Copy app code
COPY ./app ./app
COPY ./requirements.txt .

RUN pip install --no-cache-dir -r requirements.txt


COPY ./app/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]