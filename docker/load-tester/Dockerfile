FROM ubuntu:22.04

# Install system dependencies
RUN apt-get update && apt-get install -y \
    apache2-utils \
    wrk \
    curl \
    wget \
    jq \
    python3 \
    python3-pip \
    htop \
    iotop \
    net-tools \
    dstat \
    tcpdump \
    iperf3 \
    git \
    build-essential \
    software-properties-common \
    && rm -rf /var/lib/apt/lists/*

# Install advanced load testing tools
RUN pip3 install \
    requests \
    aiohttp \
    asyncio \
    numpy \
    matplotlib \
    pandas \
    psutil

# Install Vegeta (Go-based load testing tool)
RUN wget https://github.com/tsenart/vegeta/releases/download/v12.8.4/vegeta_12.8.4_linux_amd64.tar.gz \
    && tar -xzf vegeta_12.8.4_linux_amd64.tar.gz \
    && mv vegeta /usr/local/bin/ \
    && rm vegeta_12.8.4_linux_amd64.tar.gz

# Install Artillery (Node.js based)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g artillery@latest

# Install hey (Go-based HTTP load generator)
RUN wget https://hey-release.s3.us-east-2.amazonaws.com/hey_linux_amd64 \
    && chmod +x hey_linux_amd64 \
    && mv hey_linux_amd64 /usr/local/bin/hey

# Create working directories
RUN mkdir -p /scripts /results /tools

# Copy custom scripts
COPY scripts/ /tools/

# Set working directory
WORKDIR /tools

# Make scripts executable
RUN chmod +x /tools/*.sh

# Install PHP for running our custom test scripts
RUN add-apt-repository ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y \
        php8.3-cli \
        php8.3-curl \
        php8.3-mbstring \
        php8.3-xml \
        php8.3-json

# Set environment variables
ENV PATH="/tools:$PATH"
ENV RESULTS_DIR="/results"

CMD ["bash"]