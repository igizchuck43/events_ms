<?php
class Event {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create($title, $description, $event_date, $location, $max_participants, $created_by) {
        $query = "INSERT INTO events (title, description, event_date, location, max_participants, created_by) 
                  VALUES (:title, :description, :event_date, :location, :max_participants, :created_by)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":event_date", $event_date);
        $stmt->bindParam(":location", $location);
        $stmt->bindParam(":max_participants", $max_participants);
        $stmt->bindParam(":created_by", $created_by);
        
        return $stmt->execute();
    }
    
    public function update($id, $title, $description, $event_date, $location, $max_participants, $status) {
        $query = "UPDATE events 
                  SET title = :title, description = :description, event_date = :event_date, 
                      location = :location, max_participants = :max_participants, status = :status 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":event_date", $event_date);
        $stmt->bindParam(":location", $location);
        $stmt->bindParam(":max_participants", $max_participants);
        $stmt->bindParam(":status", $status);
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        $query = "DELETE FROM events WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
    
    public function getById($id) {
        $query = "SELECT e.*, u.username as creator_name, 
                  (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as current_participants 
                  FROM events e 
                  LEFT JOIN users u ON e.created_by = u.id 
                  WHERE e.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    public function getAll($status = null) {
        $query = "SELECT e.*, u.username as creator_name, 
                  (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as current_participants 
                  FROM events e 
                  LEFT JOIN users u ON e.created_by = u.id";
        
        if($status) {
            $query .= " WHERE e.status = :status";
        }
        
        $query .= " ORDER BY e.event_date ASC";
        
        $stmt = $this->conn->prepare($query);
        
        if($status) {
            $stmt->bindParam(":status", $status);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function registerUser($event_id, $user_id) {
        // Check if event has available slots
        $event = $this->getById($event_id);
        if($event['current_participants'] >= $event['max_participants']) {
            return false;
        }
        
        $query = "INSERT INTO event_registrations (event_id, user_id) VALUES (:event_id, :user_id)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":event_id", $event_id);
        $stmt->bindParam(":user_id", $user_id);
        
        return $stmt->execute();
    }
    
    public function getParticipants($event_id) {
        $query = "SELECT u.id, u.username, u.email, er.registration_date 
                  FROM event_registrations er 
                  JOIN users u ON er.user_id = u.id 
                  WHERE er.event_id = :event_id 
                  ORDER BY er.registration_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":event_id", $event_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getUserEvents($user_id) {
        $query = "SELECT e.*, u.username as creator_name, 
                  (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as current_participants 
                  FROM events e 
                  LEFT JOIN users u ON e.created_by = u.id 
                  JOIN event_registrations er ON e.id = er.event_id 
                  WHERE er.user_id = :user_id 
                  ORDER BY e.event_date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}